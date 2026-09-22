<?php

namespace Tests\Feature\Orders;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Roles\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\User;
use App\Modules\Users\Infrastructure\Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class BulkAssignOrdersTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $login = $this->postJson('/api/v1/auth/login', [
            'identifier' => env('SUPER_ADMIN_EMAIL', 'superadmin@marsal.com'),
            'password' => env('SUPER_ADMIN_PASSWORD', 'Admin@123'),
        ]);

        $this->token = $login->json('data.access_token');
    }

    public function test_admin_can_assign_multiple_orders_to_one_delivery_agent(): void
    {
        $agent = $this->createAgent();
        $firstOrder = $this->createOrder(OrderStatusEnum::Pending);
        $secondOrder = $this->createOrder(OrderStatusEnum::NoAnswer);

        $response = $this->auth()->patchJson('/api/v1/admin/orders/bulk-assign', [
            'order_ids' => [$firstOrder->order_id, $secondOrder->order_id],
            'agent_id' => $agent->delivery_agent_id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.assigned_count', 2)
            ->assertJsonCount(2, 'data.items');

        foreach ([$firstOrder, $secondOrder] as $order) {
            $this->assertDatabaseHas('orders', [
                'order_id' => $order->order_id,
                'delivery_agent_id' => $agent->delivery_agent_id,
                'status' => OrderStatusEnum::Assigned->value,
            ]);
            $this->assertDatabaseHas('order_status_history', [
                'order_id' => $order->order_id,
                'to_status_id' => OrderStatusEnum::Assigned->value,
            ]);
        }
    }

    public function test_bulk_assignment_changes_nothing_when_any_order_is_blocked(): void
    {
        $agent = $this->createAgent();
        $assignableOrder = $this->createOrder(OrderStatusEnum::Pending);
        $blockedOrder = $this->createOrder(OrderStatusEnum::Delivered);

        $this->auth()->patchJson('/api/v1/admin/orders/bulk-assign', [
            'order_ids' => [$assignableOrder->order_id, $blockedOrder->order_id],
            'agent_id' => $agent->delivery_agent_id,
        ])->assertUnprocessable();

        $this->assertDatabaseHas('orders', [
            'order_id' => $assignableOrder->order_id,
            'delivery_agent_id' => null,
            'status' => OrderStatusEnum::Pending->value,
        ]);
        $this->assertDatabaseMissing('order_status_history', [
            'order_id' => $assignableOrder->order_id,
            'to_status_id' => OrderStatusEnum::Assigned->value,
        ]);
    }

    private function createAgent(): DeliveryAgent
    {
        $user = User::factory()->create([
            'account_type' => AccountTypeEnum::DeliveryAgent->value,
        ]);

        return DeliveryAgent::query()->forceCreate([
            'delivery_agent_id' => (string) Str::uuid(),
            'user_id' => $user->user_id,
            'commission_value' => 0,
            'balance' => 0,
        ]);
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

    private function auth(): static
    {
        return $this->withHeader('Authorization', "Bearer {$this->token}");
    }
}
