<?php

namespace App\Modules\Orders\Application\UseCases\Admin;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Notifications\Application\DTOs\SendNotificationDTO;
use App\Modules\Notifications\Application\UseCases\SendNotificationUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Notifications\Domain\Events\OrderReassigned;
use App\Modules\Orders\Application\Exceptions\OrderCannotBeAssignedException;
use App\Modules\Orders\Application\Exceptions\OrderNotFoundException;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Domain\Interfaces\AdminOrderRepositoryInterface;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignOrderUseCase
{
    public function __construct(
        private AdminOrderRepositoryInterface $repository,
        private SendNotificationUseCase $sendNotification,
        private RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(string $orderId, string $agentId, string $adminUserId): Order
    {
        return $this->executeMany([$orderId], $agentId, $adminUserId)->first();
    }

    /** @return Collection<int, Order> */
    public function executeMany(array $orderIds, string $agentId, string $adminUserId): Collection
    {
        $orderIds = array_values(array_unique($orderIds));
        $agent = DeliveryAgent::query()
            ->with('user')
            ->where('delivery_agent_id', $agentId)
            ->firstOrFail();

        [$assignedOrders, $contexts] = DB::transaction(function () use ($orderIds, $agentId, $adminUserId) {
            $orders = $this->repository
                ->findManyWithRelations($orderIds)
                ->keyBy('order_id');
            $contexts = [];

            foreach ($orderIds as $orderId) {
                $order = $orders->get($orderId);

                if ($order === null) {
                    throw new OrderNotFoundException($orderId);
                }

                $currentStatus = $order->status instanceof OrderStatusEnum
                    ? $order->status
                    : OrderStatusEnum::tryFrom((int) $order->status);

                if ($currentStatus?->blocksReassignment()) {
                    throw new OrderCannotBeAssignedException;
                }

                $contexts[$orderId] = [
                    'previous_agent_id' => $order->delivery_agent_id,
                    'previous_agent_name' => $order->deliveryAgent?->user?->name,
                    'previous_status' => $currentStatus,
                ];
            }

            $assignedOrders = collect($orderIds)
                ->map(fn (string $orderId) => $this->repository->assignAgent(
                    $orderId,
                    $agentId,
                    $adminUserId,
                ));

            return [$assignedOrders, $contexts];
        });

        foreach ($assignedOrders as $order) {
            $context = $contexts[$order->order_id];

            $this->recordAssignmentAudit(
                adminUserId: $adminUserId,
                order: $order,
                previousAgentId: $context['previous_agent_id'],
                previousAgentName: $context['previous_agent_name'],
                previousStatus: $context['previous_status'],
                newAgent: $agent,
            );

            if ($context['previous_agent_id'] !== null
                && $context['previous_agent_id'] !== $agent->delivery_agent_id) {
                event(new OrderReassigned(
                    orderId: $order->order_id,
                    orderCode: $order->reference_code ?? $order->reference_no ?? '',
                    previousAgentName: $context['previous_agent_name'] ?? 'غير معروف',
                    newAgentName: $agent->user?->name ?? 'غير معروف',
                ));
            }

            $this->dispatchNotification($order, $agent);
        }

        return $assignedOrders;
    }

    private function recordAssignmentAudit(
        string $adminUserId,
        Order $order,
        ?string $previousAgentId,
        ?string $previousAgentName,
        ?OrderStatusEnum $previousStatus,
        DeliveryAgent $newAgent,
    ): void {
        $newAgentName = $newAgent->user?->name;
        $isReassignment = $previousAgentId !== null
            && $previousAgentId !== $newAgent->delivery_agent_id;

        $this->recordAudit->execute(
            userId: $adminUserId,
            event: AuditEventEnum::Assigned,
            auditableType: 'orders',
            auditableId: $order->order_id,
            oldValues: [
                'delivery_agent_id' => $previousAgentId,
                'agent_name' => $previousAgentName,
                'status' => $previousStatus?->value,
            ],
            newValues: [
                'delivery_agent_id' => $newAgent->delivery_agent_id,
                'agent_name' => $newAgentName,
                'status' => OrderStatusEnum::Assigned->value,
            ],
            metadata: [
                'action' => 'order_agent_assignment',
                'reference_code' => $order->reference_code ?? $order->reference_no,
                'is_reassignment' => $isReassignment,
                'previous_agent_id' => $previousAgentId,
                'previous_agent_name' => $previousAgentName,
                'new_agent_id' => $newAgent->delivery_agent_id,
                'new_agent_name' => $newAgentName,
            ],
        );
    }

    private function dispatchNotification(Order $order, DeliveryAgent $agent): void
    {
        if ($agent->user === null) {
            return;
        }

        $this->sendNotification->execute(new SendNotificationDTO(
            userId: $agent->user->user_id,
            notificationType: NotificationTypeEnum::NewOrder,
            titleAr: 'طلب توصيل جديد',
            bodyAr: "تم تعيين طلب #{$order->reference_code} لك",
            data: ['order_id' => $order->order_id],
            sendViaFcm: true,
        ));
    }
}
