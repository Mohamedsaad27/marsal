<?php

namespace App\Modules\Orders\Application\Services;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Domain\Services\OrderStatusExportLabelService;
use App\Modules\Orders\Infrastructure\Database\Models\Order;

final class OrderExportRowMapper
{
    public function __construct(
        private OrderStatusExportLabelService $statusLabel,
    ) {}

    /**
     * @return list<string|int|float|null>
     */
    public function map(Order $order): array
    {
        $phones = array_values(array_filter([
            $order->customerInfo?->customer_phone,
            $order->customerInfo?->phone_alt,
        ]));

        $status = $order->status instanceof OrderStatusEnum
            ? $order->status
            : OrderStatusEnum::tryFrom((int) $order->status);

        return [
            $order->reference_no ?? '',
            $order->customerInfo?->customer_name ?? '',
            $order->address?->address_line ?? '',
            $order->address?->governorate?->name_ar ?? '',
            implode(' / ', $phones),
            $order->notes ?? '',
            $order->items?->total_quantity ?? 0,
            $this->formatNullableInt($order->items?->returned_quantity),
            $this->formatDecimal($order->financials?->original_amount),
            $this->formatNullableDecimal($order->financials?->collected_amount),
            $status !== null ? $this->statusLabel->labelFor($status) : '',
            $order->display_company_name ?? '',
            $order->shippingCompany?->company_name ?? '',
            $order->deliveryAgent?->user?->name ?? '',
        ];
    }

    private function formatDecimal(mixed $value): string|float
    {
        if ($value === null) {
            return 0;
        }

        return (float) $value;
    }

    private function formatNullableDecimal(mixed $value): string|float
    {
        if ($value === null) {
            return '';
        }

        return (float) $value;
    }

    private function formatNullableInt(mixed $value): string|int
    {
        if ($value === null) {
            return '';
        }

        return (int) $value;
    }
}
