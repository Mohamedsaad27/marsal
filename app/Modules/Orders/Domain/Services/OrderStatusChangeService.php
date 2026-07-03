<?php

namespace App\Modules\Orders\Domain\Services;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Notifications\Domain\Events\AgentOrderStatusChanged;
use App\Modules\Notifications\Domain\Events\ApprovalRequested;
use App\Modules\Notifications\Domain\Events\CollectionRecorded;
use App\Modules\Notifications\Domain\Events\OrderStatusChanged;
use App\Modules\Orders\Application\DTOs\OrderStatusChangePayload;
use App\Modules\Orders\Domain\Enums\ApprovalStatusEnum;
use App\Modules\Orders\Domain\Enums\ApprovalTypeEnum;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderStatusChangeService
{
    public function __construct(
        private OrderStatusTransitionService $transitions,
        private CommissionCalculatorService $commissionCalculator,
    ) {}

    public function apply(Order $order, OrderStatusChangePayload $payload): array
    {
        $currentStatus = $order->status;
        $storedStatus = $this->transitions->resolveStoredStatus($payload->requestedStatus);
        $collectionCreated = false;
        $collectionMeta = null;
        $companyUserId = $order->shippingCompany?->user_id;
        $orderCode = $order->reference_code ?? $order->reference_no;

        DB::transaction(function () use (
            $payload,
            $order,
            $currentStatus,
            $storedStatus,
            &$collectionCreated,
            &$collectionMeta,
        ) {
            $now = now();

            $this->applyStatusSideEffects($order, $payload, $now);

            if ($this->shouldCreateCollection($payload)) {
                $collectionMeta = $this->recordCollection($order, $payload, $now);
                $collectionCreated = true;
                $this->updateCollectedAmount($order, $payload->collectedAmount ?? 0.0, $now);
            }

            $this->persistOrderStatus($order, $storedStatus, $now);
            $this->recordStatusHistory($order, $currentStatus, $storedStatus, $payload, $now);
        });

        $this->dispatchStatusEvents($payload, $order, $storedStatus, $companyUserId, $orderCode);
        $this->dispatchSuperAdminEvents($payload, $order, $storedStatus, $orderCode, $collectionMeta);

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
        OrderStatusChangePayload $payload,
        DateTimeInterface $now,
    ): void {
        match ($payload->requestedStatus) {
            OrderStatusEnum::Postponed => $this->recordPostponement($order, $payload, $now),
            OrderStatusEnum::DeliveredPriceChanged => $this->recordPriceChangeApprovalRequest($order, $payload, $now),
            default => null,
        };
    }

    private function shouldCreateCollection(OrderStatusChangePayload $payload): bool
    {
        return $payload->requestedStatus->requiresCollection()
            && $payload->requestedStatus !== OrderStatusEnum::DeliveredPriceChanged;
    }

    private function recordPostponement(
        Order $order,
        OrderStatusChangePayload $payload,
        DateTimeInterface $now,
    ): void {
        DB::table('order_schedules')->upsert(
            [
                [
                    'order_schedule_id' => (string) Str::uuid(),
                    'order_id' => $order->order_id,
                    'expected_delivery_date' => null,
                    'postponed_date' => $payload->postponedDate,
                    'schedule_notes' => $payload->notes,
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
            'delivery_agent_id' => $payload->deliveryAgentId,
            'scheduled_date' => $payload->postponedDate,
            'reason' => $payload->notes,
            'reminder_sent' => false,
            'is_reassigned' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function recordPriceChangeApprovalRequest(
        Order $order,
        OrderStatusChangePayload $payload,
        DateTimeInterface $now,
    ): void {
        $originalAmount = (float) ($order->financials?->original_amount ?? 0);
        $requestedAmount = $payload->newCodAmount ?? $payload->collectedAmount ?? 0;

        DB::table('approval_requests')->insert([
            'approval_request_id' => (string) Str::uuid(),
            'order_id' => $order->order_id,
            'approval_type' => ApprovalTypeEnum::PriceChange->value,
            'approval_status' => ApprovalStatusEnum::Pending->value,
            'requested_by' => $payload->changedByUserId,
            'original_amount' => $originalAmount,
            'requested_amount' => $requestedAmount,
            'reason' => $payload->notes,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function recordCollection(
        Order $order,
        OrderStatusChangePayload $payload,
        DateTimeInterface $now,
    ): array {
        $collectedAmount = $payload->collectedAmount ?? 0.0;
        $commissionValue = $this->resolveAgentCommissionValue($payload->deliveryAgentId);

        $commission = $this->commissionCalculator->calculate(
            collectedAmount: $collectedAmount,
            commissionValue: $commissionValue,
        );

        $collectionId = (string) Str::uuid();

        DB::table('collections')->insert([
            'collection_id' => $collectionId,
            'order_id' => $order->order_id,
            'delivery_agent_id' => $payload->deliveryAgentId,
            'shipping_company_id' => $order->shipping_company_id,
            'collection_type' => $payload->collectionType?->value ?? CollectionTypeEnum::Cod->value,
            'collected_amount' => $collectedAmount,
            'commission_amount' => $commission['commission_amount'],
            'net_due' => $commission['net_due'],
            'collected_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DeliveryAgent::query()
            ->whereKey($payload->deliveryAgentId)
            ->increment('balance', $collectedAmount);

        return [
            'collection_id' => $collectionId,
            'collected_amount' => number_format((float) $collectedAmount, 2, '.', ''),
        ];
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
        OrderStatusChangePayload $payload,
        DateTimeInterface $now,
    ): void {
        DB::table('order_status_history')->insert([
            'order_status_history_id' => (string) Str::uuid(),
            'order_id' => $order->order_id,
            'from_status_id' => $fromStatus->value,
            'to_status_id' => $toStatus->value,
            'changed_by' => $payload->changedByUserId,
            'notes' => $payload->notes,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function dispatchStatusEvents(
        OrderStatusChangePayload $payload,
        Order $order,
        OrderStatusEnum $storedStatus,
        ?string $companyUserId,
        ?string $orderCode,
    ): void {
        if ($payload->requestedStatus === OrderStatusEnum::DeliveredPriceChanged) {
            $actorName = User::query()
                ->where('user_id', $payload->changedByUserId)
                ->value('name') ?? 'المندوب';

            event(new ApprovalRequested(
                companyUserId: $companyUserId ?? '',
                orderCode: $orderCode ?? '',
                orderId: $order->order_id,
                agentName: $actorName,
                newAmount: (string) ($payload->newCodAmount ?? $payload->collectedAmount ?? 0),
            ));

            return;
        }

        if (! $companyUserId) {
            return;
        }

        event(new OrderStatusChanged(
            companyUserId: $companyUserId,
            orderCode: $orderCode,
            orderId: $order->order_id,
            statusLabelAr: $storedStatus->labelAr(),
        ));
    }

    private function dispatchSuperAdminEvents(
        OrderStatusChangePayload $payload,
        Order $order,
        OrderStatusEnum $storedStatus,
        ?string $orderCode,
        ?array $collectionMeta,
    ): void {
        if (! $payload->notifySuperAdminsOnAgentStatusChange) {
            if ($collectionMeta !== null) {
                $orderCode ??= $order->reference_code ?? $order->reference_no ?? '';

                event(new CollectionRecorded(
                    orderId: $order->order_id,
                    orderCode: $orderCode,
                    collectionId: $collectionMeta['collection_id'],
                    agentName: 'الإدارة',
                    collectedAmount: $collectionMeta['collected_amount'],
                ));
            }

            return;
        }

        $actorName = User::query()
            ->where('user_id', $payload->changedByUserId)
            ->value('name') ?? 'المندوب';

        $orderCode ??= $order->reference_code ?? $order->reference_no ?? '';

        if ($payload->requestedStatus === OrderStatusEnum::DeliveredPriceChanged) {
            return;
        }

        event(new AgentOrderStatusChanged(
            orderId: $order->order_id,
            orderCode: $orderCode,
            agentName: $actorName,
            statusLabelAr: $storedStatus->labelAr(),
        ));

        if ($collectionMeta !== null) {
            event(new CollectionRecorded(
                orderId: $order->order_id,
                orderCode: $orderCode,
                collectionId: $collectionMeta['collection_id'],
                agentName: $actorName,
                collectedAmount: $collectionMeta['collected_amount'],
            ));
        }
    }
}
