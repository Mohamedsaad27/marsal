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

    public function test_status_change_creates_return_record_without_relying_on_model_observer(): void
    {
        Event::fake();

        $admin = User::factory()->create([
            'user_id' => (string) Str::uuid(),
            'account_type' => AccountTypeEnum::SuperAdmin->value,
        ]);
        $order = Order::query()->forceCreate([
            'order_id' => (string) Str::uuid(),
            'reference_no' => 'EXT-NO-ANSWER',
            'reference_code' => 'TEST-NO-ANSWER',
            'status' => OrderStatusEnum::OutForDelivery->value,
        ]);

        Order::withoutEvents(fn () => app(OrderStatusChangeService::class)->apply(
            $order,
            new OrderStatusChangePayload(
                changedByUserId: $admin->user_id,
                deliveryAgentId: '',
                requestedStatus: OrderStatusEnum::NoAnswer,
                notes: 'لا يوجد رد من العميل',
                notifySuperAdminsOnAgentStatusChange: false,
            ),
        ));

        $this->assertDatabaseHas('returns', [
            'order_id' => $order->order_id,
            'return_status' => 1,
            'return_reason' => OrderStatusEnum::NoAnswer->labelAr(),
        ]);
    }

    public function test_reverting_collected_order_removes_signed_agent_and_company_balances(): void
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
            'balance' => 475,
        ]);

        $company = ShippingCompany::query()->forceCreate([
            'shipping_company_id' => (string) Str::uuid(),
            'user_id' => $companyUser->user_id,
            'company_name' => 'Acme Logistics',
            'commission_value' => 40,
            'balance' => 460,
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
            'agent_commission_amount' => 25,
            'system_commission_amount' => 40,
            'net_due_company' => 460,
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
            'agent_commission_amount' => 25,
            'agent_net_due' => 475,
            'system_commission_amount' => 40,
            'company_net_due' => 460,
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
        $company->refresh();

        $this->assertSame('0.00', $agent->balance);
        $this->assertSame('0.00', $company->balance);
        $this->assertSame(0.0, (float) $agent->balance);
        $this->assertDatabaseHas('orders', [
            'order_id' => $order->order_id,
            'status' => OrderStatusEnum::OutForDelivery->value,
            'delivered_at' => null,
        ]);
        $this->assertDatabaseHas('order_financials', [
            'order_id' => $order->order_id,
            'collected_amount' => 0,
            'agent_commission_amount' => 0,
            'system_commission_amount' => 0,
            'net_due_company' => 0,
        ]);
        $this->assertDatabaseHas('collections', [
            'order_id' => $order->order_id,
            'collected_amount' => 0,
            'agent_commission_amount' => 0,
            'agent_net_due' => 0,
            'system_commission_amount' => 0,
            'company_net_due' => 0,
        ]);
    }
}
