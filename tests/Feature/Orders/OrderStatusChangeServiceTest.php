<?php

namespace Tests\Feature\Orders;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Orders\Application\DTOs\OrderStatusChangePayload;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Domain\Services\OrderStatusChangeService;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderStatusChangeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reverting_collected_order_to_non_collection_status_deducts_agent_collection(): void
    {
        Event::fake();

        $admin = User::factory()->create([
            'user_id' => (string) Str::uuid(),
            'account_type' => AccountTypeEnum::SuperAdmin->value,
        ]);
        $agentUser = User::factory()->create([
            'user_id' => (string) Str::uuid(),
            'account_type' => AccountTypeEnum::DeliveryAgent->value,
        ]);
        $companyUser = User::factory()->create([
            'user_id' => (string) Str::uuid(),
            'account_type' => AccountTypeEnum::ShippingCompany->value,
        ]);

        $agent = DeliveryAgent::query()->forceCreate([
            'delivery_agent_id' => (string) Str::uuid(),
            'user_id' => $agentUser->user_id,
            'commission_value' => 25,
            'balance' => 750,
        ]);

        $company = ShippingCompany::query()->forceCreate([
            'shipping_company_id' => (string) Str::uuid(),
            'user_id' => $companyUser->user_id,
            'company_name' => 'Acme Logistics',
        ]);

        $order = Order::query()->forceCreate([
            'order_id' => (string) Str::uuid(),
            'reference_no' => 'EXT-100',
            'reference_code' => 'ACME-100',
            'shipping_company_id' => $company->shipping_company_id,
            'delivery_agent_id' => $agent->delivery_agent_id,
            'status' => OrderStatusEnum::Delivered->value,
            'delivered_at' => now(),
        ]);

        DB::table('order_financials')->insert([
            'order_financial_id' => (string) Str::uuid(),
            'order_id' => $order->order_id,
            'original_amount' => 500,
            'collected_amount' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('collections')->insert([
            'collection_id' => (string) Str::uuid(),
            'order_id' => $order->order_id,
            'delivery_agent_id' => $agent->delivery_agent_id,
            'shipping_company_id' => $company->shipping_company_id,
            'collection_type' => CollectionTypeEnum::Cod->value,
            'collected_amount' => 500,
            'commission_amount' => 25,
            'net_due' => 475,
            'collected_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(OrderStatusChangeService::class)->apply($order->fresh(['financials', 'shippingCompany']), new OrderStatusChangePayload(
            changedByUserId: $admin->user_id,
            deliveryAgentId: $agent->delivery_agent_id,
            requestedStatus: OrderStatusEnum::OutForDelivery,
            notifySuperAdminsOnAgentStatusChange: false,
        ));

        $agent->refresh();

        $this->assertSame('250.00', $agent->balance);
        $this->assertDatabaseHas('orders', [
            'order_id' => $order->order_id,
            'status' => OrderStatusEnum::OutForDelivery->value,
            'delivered_at' => null,
        ]);
        $this->assertDatabaseHas('order_financials', [
            'order_id' => $order->order_id,
            'collected_amount' => 0,
        ]);
        $this->assertDatabaseHas('collections', [
            'order_id' => $order->order_id,
            'collected_amount' => 0,
            'commission_amount' => 0,
            'net_due' => 0,
        ]);
    }
}
