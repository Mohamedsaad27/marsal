<?php

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->leftJoin('order_items', function ($join) {
                $join->on('order_items.order_id', '=', 'orders.order_id')
                    ->whereNull('order_items.deleted_at');
            })
            ->whereNull('orders.deleted_at')
            ->whereNotIn('orders.status', OrderStatusEnum::returnsPageExcludedIds())
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('returns')
                    ->whereColumn('returns.order_id', 'orders.order_id')
                    ->whereNull('returns.deleted_at');
            })
            ->select([
                'orders.order_id',
                'orders.delivery_agent_id',
                'orders.shipping_company_id',
                'orders.status',
                'order_items.total_quantity',
                'order_items.returned_quantity',
            ])
            ->chunkById(500, function ($orders): void {
                $now = now();
                $rows = [];

                foreach ($orders as $order) {
                    $status = OrderStatusEnum::tryFrom((int) $order->status);
                    $quantity = $order->returned_quantity
                        ?? $order->total_quantity
                        ?? 1;

                    $rows[] = [
                        'return_id' => (string) Str::uuid(),
                        'order_id' => $order->order_id,
                        'delivery_agent_id' => $order->delivery_agent_id,
                        'shipping_company_id' => $order->shipping_company_id,
                        'return_status' => 1,
                        'returned_quantity' => max(0, (int) $quantity),
                        'return_reason' => $status?->labelAr(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('returns')->insert($rows);
                }
            }, 'orders.order_id', 'order_id');
    }

    public function down(): void
    {
        // The backfilled rows are operational data and must not be deleted on rollback.
    }
};
