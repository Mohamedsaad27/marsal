<?php

namespace App\Modules\Returns\Application\Services;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Returns\Domain\Enums\ReturnStatusEnum;
use App\Modules\Returns\Infrastructure\Database\Models\OrderReturn;
use Illuminate\Support\Str;

class ReturnRecordSynchronizer
{
    public function sync(Order $order): ?OrderReturn
    {
        $statusId = (int) $order->getRawOriginal('status');

        if (in_array($statusId, OrderStatusEnum::returnsPageExcludedIds(), true)) {
            return null;
        }

        $item = $order->items()->first();
        $returnedQuantity = $item?->returned_quantity
            ?? $item?->total_quantity
            ?? 1;
        $status = OrderStatusEnum::tryFrom($statusId);

        $record = OrderReturn::withTrashed()->firstOrNew([
            'order_id' => $order->order_id,
        ]);

        if (! $record->exists) {
            $record->return_id = (string) Str::uuid();
            $record->return_status = ReturnStatusEnum::Pending;
            $record->return_reason = $status?->labelAr();
        }

        if ($record->trashed()) {
            $record->restore();
        }

        $record->delivery_agent_id = $order->delivery_agent_id;
        $record->shipping_company_id = $order->shipping_company_id;
        $record->returned_quantity = max(0, (int) $returnedQuantity);
        $record->save();

        return $record;
    }
}
