<?php

namespace Tests\Feature\Returns;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Returns\Domain\Interfaces\ReturnRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReturnPageMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_contains_every_order_status_except_the_four_excluded_statuses(): void
    {
        $ordersByStatus = [];

        foreach (OrderStatusEnum::cases() as $status) {
            $ordersByStatus[$status->value] = $this->createOrder($status);
        }

        $paginator = app(ReturnRepositoryInterface::class)->paginate(
            status: null,
            companyId: null,
            agentId: null,
            perPage: 100,
        );

        $actualOrderIds = collect($paginator->items())
            ->pluck('order_id')
            ->sort()
            ->values()
            ->all();

        $expectedOrderIds = collect($ordersByStatus)
            ->reject(fn (Order $order, int $statusId) => in_array(
                $statusId,
                OrderStatusEnum::returnsPageExcludedIds(),
                true,
            ))
            ->pluck('order_id')
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expectedOrderIds, $actualOrderIds);
    }

    public function test_order_is_hidden_again_when_it_returns_to_an_excluded_status(): void
    {
        $order = $this->createOrder(OrderStatusEnum::NoAnswer);

        $this->assertDatabaseHas('returns', ['order_id' => $order->order_id]);
        $this->assertSame(1, app(ReturnRepositoryInterface::class)->paginate(null, null, null, 20)->total());

        $order->update(['status' => OrderStatusEnum::OutForDelivery->value]);

        $this->assertDatabaseHas('returns', ['order_id' => $order->order_id]);
        $this->assertSame(0, app(ReturnRepositoryInterface::class)->paginate(null, null, null, 20)->total());
    }

    private function createOrder(OrderStatusEnum $status): Order
    {
        $suffix = (string) Str::uuid();

        return Order::query()->forceCreate([
            'order_id' => (string) Str::uuid(),
            'reference_no' => "EXT-{$suffix}",
            'reference_code' => "TEST-{$suffix}",
            'status' => $status->value,
        ]);
    }
}
