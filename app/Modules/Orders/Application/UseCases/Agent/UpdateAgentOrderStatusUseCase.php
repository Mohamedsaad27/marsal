<?php

namespace App\Modules\Orders\Application\UseCases\Agent;

use App\Modules\Notifications\Domain\Events\ApprovalRequested;
use App\Modules\Notifications\Domain\Events\OrderStatusChanged;
use App\Modules\Orders\Application\DTOs\UpdateAgentOrderStatusDTO;
use App\Modules\Orders\Application\Exceptions\InvalidOrderStatusTransitionException;
use App\Modules\Orders\Application\Exceptions\OrderNotFoundException;
use App\Modules\Orders\Domain\Enums\ApprovalStatusEnum;
use App\Modules\Orders\Domain\Enums\ApprovalTypeEnum;
use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Domain\Interfaces\AgentOrderRepositoryInterface;
use App\Modules\Orders\Domain\Services\CommissionCalculatorService;
use App\Modules\Orders\Domain\Services\OrderStatusTransitionService;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateAgentOrderStatusUseCase
{
    public function __construct(
        private AgentOrderRepositoryInterface $orders,
        private OrderStatusTransitionService $transitions,
        private CommissionCalculatorService $commissionCalculator,
    ) {}

    public function execute(UpdateAgentOrderStatusDTO $dto): array
    {
        $order = $this->orders->findForAgent($dto->orderId, $dto->deliveryAgentId);

        if ($order === null) {
            throw new OrderNotFoundException();
        }

        $currentStatus = $order->status;

        try {
            $this->transitions->assertCanTransition($currentStatus, $dto->requestedStatus);
        } catch (\InvalidArgumentException) {
            throw new InvalidOrderStatusTransitionException();
        }

        $storedStatus = $this->transitions->resolveStoredStatus($dto->requestedStatus);
        $collectionCreated = false;
        $companyUserId = $order->shippingCompany?->user_id;
        $orderCode = $order->reference_code ?? $order->reference_no;

        DB::transaction(function () use (
            $dto,
            $order,
            $currentStatus,
            $storedStatus,
            &$collectionCreated,
        ) {
            $now = now();

            $this->applyStatusSideEffects($order, $dto, $now);

            if ($this->shouldCreateCollection($dto)) {
                $this->recordCollection($order, $dto, $now);
                $collectionCreated = true;
                $this->updateCollectedAmount($order, $dto->collectedAmount ?? 0.0, $now);
            }

            $this->persistOrderStatus($order, $storedStatus, $now);
            $this->recordStatusHistory($order, $currentStatus, $storedStatus, $dto, $now);
        });

        $this->dispatchStatusEvents($dto, $order, $storedStatus, $companyUserId, $orderCode);

        return [
            'order_id' => $order->order_id,
            'new_status' => [
                'id' => $storedStatus->value,
                'label' => $storedStatus->labelAr(),
            ],
            'collection_created' => $collectionCreated,
        ];
    }

    private function applyStatusSideEffects(
        Order $order,
        UpdateAgentOrderStatusDTO $dto,
        DateTimeInterface $now,
    ): void {
        match ($dto->requestedStatus) {
            OrderStatusEnum::Postponed => $this->recordPostponement($order, $dto, $now),
            OrderStatusEnum::DeliveredPriceChanged => $this->recordPriceChangeApprovalRequest($order, $dto, $now),
            default => null,
        };
    }

    private function shouldCreateCollection(UpdateAgentOrderStatusDTO $dto): bool
    {
        return $dto->requestedStatus->requiresCollection()
            && $dto->requestedStatus !== OrderStatusEnum::DeliveredPriceChanged;
    }

    private function recordPostponement(
        Order $order,
        UpdateAgentOrderStatusDTO $dto,
        DateTimeInterface $now,
    ): void {
        DB::table('order_schedules')->upsert(
            [
                [
                    'order_schedule_id' => (string) Str::uuid(),
                    'order_id' => $order->order_id,
                    'expected_delivery_date' => null,
                    'postponed_date' => $dto->postponedDate,
                    'schedule_notes' => $dto->notes,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            ['order_id'],
            [
                'postponed_date',
                'schedule_notes',
                'updated_at',
            ],
        );

        DB::table('postponed_schedules')->insert([
            'postponed_schedule_id' => (string) Str::uuid(),
            'order_id' => $order->order_id,
            'delivery_agent_id' => $dto->deliveryAgentId,
            'scheduled_date' => $dto->postponedDate,
            'reason' => $dto->notes,
            'reminder_sent' => false,
            'is_reassigned' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function recordPriceChangeApprovalRequest(
        Order $order,
        UpdateAgentOrderStatusDTO $dto,
        DateTimeInterface $now,
    ): void {
        $originalAmount = (float) ($order->financials?->original_amount ?? 0);
        $requestedAmount = $dto->newCodAmount ?? $dto->collectedAmount ?? 0;

        DB::table('approval_requests')->insert([
            'approval_request_id' => (string) Str::uuid(),
            'order_id' => $order->order_id,
            'approval_type' => ApprovalTypeEnum::PriceChange->value,
            'approval_status' => ApprovalStatusEnum::Pending->value,
            'requested_by' => $dto->userId,
            'original_amount' => $originalAmount,
            'requested_amount' => $requestedAmount,
            'reason' => $dto->notes,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function recordCollection(
        Order $order,
        UpdateAgentOrderStatusDTO $dto,
        DateTimeInterface $now,
    ): void {
        $collectedAmount = $dto->collectedAmount ?? 0.0;
        $commissionValue = $this->resolveAgentCommissionValue($dto->deliveryAgentId);

        $commission = $this->commissionCalculator->calculate(
            collectedAmount: $collectedAmount,
            commissionValue: $commissionValue,
        );

        DB::table('collections')->insert([
            'collection_id' => (string) Str::uuid(),
            'order_id' => $order->order_id,
            'delivery_agent_id' => $dto->deliveryAgentId,
            'shipping_company_id' => $order->shipping_company_id,
            'collection_type' => $dto->collectionType?->value ?? CollectionTypeEnum::Cod->value,
            'collected_amount' => $collectedAmount,
            'commission_amount' => $commission['commission_amount'],
            'net_due' => $commission['net_due'],
            'collected_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DeliveryAgent::query()
            ->whereKey($dto->deliveryAgentId)
            ->increment('balance', $collectedAmount);
    }

    private function resolveAgentCommissionValue(string $deliveryAgentId): float
    {
        $agent = DeliveryAgent::query()
            ->whereKey($deliveryAgentId)
            ->firstOrFail(['commission_value']);

        return (float) $agent->commission_value;
    }

    private function updateCollectedAmount(
        Order $order,
        float $collectedAmount,
        DateTimeInterface $now,
    ): void {
        DB::table('order_financials')
            ->where('order_id', $order->order_id)
            ->update([
                'collected_amount' => $collectedAmount,
                'updated_at' => $now,
            ]);
    }

    private function persistOrderStatus(
        Order $order,
        OrderStatusEnum $storedStatus,
        DateTimeInterface $now,
    ): void {
        $order->status = $storedStatus;

        if ($storedStatus->isTerminal()) {
            $order->delivered_at = $now;
        }

        $order->save();
    }

    private function recordStatusHistory(
        Order $order,
        OrderStatusEnum $fromStatus,
        OrderStatusEnum $toStatus,
        UpdateAgentOrderStatusDTO $dto,
        DateTimeInterface $now,
    ): void {
        DB::table('order_status_history')->insert([
            'order_status_history_id' => (string) Str::uuid(),
            'order_id' => $order->order_id,
            'from_status_id' => $fromStatus->value,
            'to_status_id' => $toStatus->value,
            'changed_by' => $dto->userId,
            'notes' => $dto->notes,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function dispatchStatusEvents(
        UpdateAgentOrderStatusDTO $dto,
        Order $order,
        OrderStatusEnum $storedStatus,
        ?string $companyUserId,
        ?string $orderCode,
    ): void {
        if (! $companyUserId) {
            return;
        }

        if ($dto->requestedStatus === OrderStatusEnum::DeliveredPriceChanged) {
            $agentName = User::query()
                ->where('user_id', $dto->userId)
                ->value('name') ?? 'المندوب';

            event(new ApprovalRequested(
                companyUserId: $companyUserId,
                orderCode: $orderCode,
                orderId: $order->order_id,
                agentName: $agentName,
                newAmount: (string) ($dto->newCodAmount ?? $dto->collectedAmount ?? 0),
            ));

            return;
        }

        event(new OrderStatusChanged(
            companyUserId: $companyUserId,
            orderCode: $orderCode,
            orderId: $order->order_id,
            statusLabelAr: $storedStatus->labelAr(),
        ));
    }
}
