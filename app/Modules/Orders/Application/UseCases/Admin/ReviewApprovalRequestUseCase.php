<?php

namespace App\Modules\Orders\Application\UseCases\Admin;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Notifications\Application\DTOs\SendNotificationDTO;
use App\Modules\Notifications\Application\UseCases\SendNotificationUseCase;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Orders\Domain\Enums\ApprovalStatusEnum;
use App\Modules\Orders\Domain\Enums\ApprovalTypeEnum;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Domain\Interfaces\ApprovalRequestRepositoryInterface;
use App\Modules\Orders\Domain\Services\CommissionCalculatorService;
use App\Modules\Orders\Infrastructure\Database\Models\ApprovalRequest;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Orders\Infrastructure\Database\Models\OrderStatusHistory;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ReviewApprovalRequestUseCase
{
    public function __construct(
        private ApprovalRequestRepositoryInterface $repository,
        private CommissionCalculatorService $commissionCalculator,
        private SendNotificationUseCase $sendNotification,
    ) {}

    public function execute(
        string $approvalRequestId,
        string $action,
        string $adminUserId,
        ?string $reviewNotes,
    ): ApprovalRequest {
        $record = $this->repository->findWithRelations($approvalRequestId);

        if ($record === null) {
            throw new NotFoundHttpException(__('orders::messages.approval_not_found'));
        }

        if ($record->approval_status !== ApprovalStatusEnum::Pending) {
            throw new UnprocessableEntityHttpException(
                __('orders::messages.approval_already_reviewed')
            );
        }

        $newStatus = $action === 'approve'
            ? ApprovalStatusEnum::Approved->value
            : ApprovalStatusEnum::Rejected->value;

        $reviewed = DB::transaction(function () use ($record, $newStatus, $adminUserId, $reviewNotes, $action) {
            $reviewed = $this->repository->markReviewed(
                $record->approval_request_id,
                $newStatus,
                $adminUserId,
                $reviewNotes,
            );

            $this->applyOrderEffect($reviewed, $action, $adminUserId);

            return $reviewed;
        });

        $this->notifyCompany($reviewed);

        return $reviewed;
    }

    private function applyOrderEffect(ApprovalRequest $record, string $action, string $adminUserId): void
    {
        $order = Order::query()
            ->with('financials')
            ->lockForUpdate()
            ->findOrFail($record->order_id);

        $fromStatus = $order->status instanceof OrderStatusEnum
            ? $order->status->value
            : (int) $order->status;

        $toStatus = match (true) {
            $action === 'approve' => $this->resolveApprovedStatus($record->approval_type),
            default               => OrderStatusEnum::OutForDelivery->value,
        };

        $toStatusEnum = OrderStatusEnum::from($toStatus);

        $order->update([
            'status' => $toStatus,
            'delivered_at' => $toStatusEnum->isTerminal() ? now() : null,
        ]);

        if ($action === 'approve') {
            $this->applyApprovedFinancialEffect($order, $record);
        }

        OrderStatusHistory::create([
            'order_status_history_id' => (string) Str::uuid(),
            'order_id'                => $order->order_id,
            'from_status_id'          => $fromStatus,
            'to_status_id'            => $toStatus,
            'changed_by'              => $adminUserId,
            'notes'                   => $record->review_notes,
        ]);
    }

    private function applyApprovedFinancialEffect(Order $order, ApprovalRequest $record): void
    {
        if ($order->delivery_agent_id === null) {
            return;
        }

        $collectedAmount = (float) $record->requested_amount;
        $commissionValue = $this->resolveAgentCommissionValue($order->delivery_agent_id);
        $commission = $this->commissionCalculator->calculate(
            collectedAmount: $collectedAmount,
            commissionValue: $commissionValue,
        );

        $this->upsertCollectionForApproval(
            order: $order,
            record: $record,
            collectedAmount: $collectedAmount,
            commissionAmount: (float) $commission['commission_amount'],
            netDue: (float) $commission['net_due'],
        );

        $order->financials?->update([
            'approved_amount' => $record->requested_amount,
            'collected_amount' => $collectedAmount,
            'commission_amount' => $commission['commission_amount'],
            'net_due_company' => $commission['net_due'],
        ]);
    }

    private function upsertCollectionForApproval(
        Order $order,
        ApprovalRequest $record,
        float $collectedAmount,
        float $commissionAmount,
        float $netDue,
    ): void {
        $collection = DB::table('collections')
            ->where('order_id', $order->order_id)
            ->whereNull('deleted_at')
            ->whereNull('settlement_id')
            ->lockForUpdate()
            ->first();

        $previousCollectedAmount = (float) ($collection->collected_amount ?? 0);
        $collectionId = $collection->collection_id ?? (string) Str::uuid();
        $now = now();

        $attributes = [
            'delivery_agent_id' => $order->delivery_agent_id,
            'shipping_company_id' => $order->shipping_company_id,
            'collection_type' => $this->resolveCollectionType($record->approval_type)->value,
            'collected_amount' => $collectedAmount,
            'commission_amount' => $commissionAmount,
            'net_due' => $netDue,
            'cash_received_at' => null,
            'cash_received_by' => null,
            'collected_at' => $now,
            'updated_at' => $now,
        ];

        if ($collection === null) {
            DB::table('collections')->insert(array_merge($attributes, [
                'collection_id' => $collectionId,
                'order_id' => $order->order_id,
                'created_at' => $now,
            ]));
        } else {
            DB::table('collections')
                ->where('collection_id', $collectionId)
                ->update($attributes);
        }

        DeliveryAgent::query()
            ->whereKey($order->delivery_agent_id)
            ->increment('balance', $collectedAmount - $previousCollectedAmount);
    }

    private function resolveAgentCommissionValue(string $deliveryAgentId): float
    {
        return (float) DeliveryAgent::query()
            ->whereKey($deliveryAgentId)
            ->value('commission_value');
    }

    private function resolveCollectionType(ApprovalTypeEnum $type): CollectionTypeEnum
    {
        return match ($type) {
            ApprovalTypeEnum::PriceChange => CollectionTypeEnum::Cod,
            ApprovalTypeEnum::PartialAmount => CollectionTypeEnum::Partial,
            ApprovalTypeEnum::ShippingFee => CollectionTypeEnum::ShippingFee,
        };
    }

    private function resolveApprovedStatus(ApprovalTypeEnum $type): int
    {
        return match ($type) {
            ApprovalTypeEnum::PriceChange   => OrderStatusEnum::DeliveredPriceChanged->value,
            ApprovalTypeEnum::PartialAmount => OrderStatusEnum::PartialDelivery->value,
            ApprovalTypeEnum::ShippingFee   => OrderStatusEnum::RefusedPaidShipping->value,
        };
    }

    private function notifyCompany(ApprovalRequest $record): void
    {
        $companyUser = $record->order?->shippingCompany?->user;

        if ($companyUser === null) {
            return;
        }

        $statusLabel = $record->approval_status === ApprovalStatusEnum::Approved
            ? 'تمت الموافقة'
            : 'تم الرفض';

        $this->sendNotification->execute(new SendNotificationDTO(
            userId:           $companyUser->user_id,
            notificationType: NotificationTypeEnum::StatusChange,
            titleAr:          "تحديث طلب الموافقة — {$statusLabel}",
            bodyAr:           "طلب الموافقة على الطلب #{$record->order?->reference_code} — {$statusLabel}",
            data:             [
                'order_id'            => $record->order_id,
                'approval_request_id' => $record->approval_request_id,
            ],
            sendViaFcm: true,
        ));
    }
}
