<?php

namespace App\Modules\Orders\Domain\Services;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Orders\Domain\Enums\OrderProofFileTypeEnum;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use Illuminate\Support\Str;

class AgentReferenceDefinitionsService
{
    public function __construct(
        private OrderStatusTransitionService $transitions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return [
            'order_statuses' => $this->orderStatuses(),
            'collection_types' => $this->collectionTypes(),
            'proof_file_types' => $this->proofFileTypes(),
            'order_list_filters' => $this->orderListFilters(),
            'collection_settled_filters' => $this->collectionSettledFilters(),
            'refusal_resolutions' => $this->refusalResolutions(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function orderStatuses(): array
    {
        return array_map(
            fn (OrderStatusEnum $status) => [
                'id' => $status->value,
                'code' => Str::snake($status->name),
                'label_ar' => $status->labelAr(),
                'is_terminal' => $status->isTerminal(),
                'requires_collection' => $status->requiresCollection(),
                'badge_color' => $status->badgeColor(),
                'available_actions' => $this->transitions->availableActions($status),
            ],
            OrderStatusEnum::cases(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectionTypes(): array
    {
        return array_map(
            fn (CollectionTypeEnum $type) => [
                'id' => $type->value,
                'code' => Str::snake($type->name),
                'label_ar' => $type->labelAr(),
            ],
            CollectionTypeEnum::cases(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function proofFileTypes(): array
    {
        return array_map(
            fn (OrderProofFileTypeEnum $type) => [
                'id' => $type->value,
                'code' => Str::snake($type->name),
                'label_ar' => $type->labelAr(),
            ],
            OrderProofFileTypeEnum::cases(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function orderListFilters(): array
    {
        return [
            [
                'key' => 'all',
                'label_ar' => 'الكل',
                'status_ids' => OrderStatusEnum::activeIds(),
            ],
            [
                'key' => 'new',
                'label_ar' => 'جديد',
                'status_ids' => [
                    OrderStatusEnum::Pending->value,
                    OrderStatusEnum::Assigned->value,
                ],
            ],
            [
                'key' => 'in_delivery',
                'label_ar' => 'قيد التوصيل',
                'status_ids' => [OrderStatusEnum::OutForDelivery->value],
            ],
            [
                'key' => 'postponed',
                'label_ar' => 'مؤجل',
                'status_ids' => [OrderStatusEnum::Postponed->value],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectionSettledFilters(): array
    {
        return [
            [
                'key' => 'unsettled',
                'query_value' => 'false',
                'label_ar' => 'غير مسوّاة',
                'description_ar' => 'تحصيلات لم يتم تسليمها للإدارة بعد',
            ],
            [
                'key' => 'settled',
                'query_value' => 'true',
                'label_ar' => 'مسوّاة',
                'description_ar' => 'تحصيلات مرتبطة بتسوية مدفوعة',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function refusalResolutions(): array
    {
        return [
            [
                'id' => 1,
                'code' => 'delivered',
                'label_ar' => 'وافق العميل أثناء المؤقت',
                'result_status_id' => OrderStatusEnum::Delivered->value,
                'requires_collection' => true,
            ],
            [
                'id' => 2,
                'code' => 'refused_paid',
                'label_ar' => 'رفض ودفع رسوم الشحن',
                'result_status_id' => OrderStatusEnum::RefusedPaidShipping->value,
                'requires_collection' => true,
            ],
            [
                'id' => 3,
                'code' => 'refused_no_pay',
                'label_ar' => 'رفض وعدم الدفع',
                'result_status_id' => OrderStatusEnum::RefusedNoPayment->value,
                'requires_collection' => false,
            ],
            [
                'id' => 4,
                'code' => 'expired',
                'label_ar' => 'انتهى المؤقت بدون حل',
                'result_status_id' => OrderStatusEnum::RefusedNoPayment->value,
                'requires_collection' => false,
            ],
        ];
    }
}
