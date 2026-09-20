<?php

namespace Tests\Feature\Orders;

use App\Modules\Collections\Application\UseCases\Admin\MarkCashReceivedUseCase;
use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Domain\Services\RecordCollectionService;
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

class RecordCollectionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_snapshots_commissions_and_applies_balance_deltas_on_update(): void
    {
        [$order, $agent, $company] = $this->createOrderContext(50, 80);
        $service = app(RecordCollectionService::class);

        $service->record(
            order: $order,
            deliveryAgentId: $agent->delivery_agent_id,
            collectionType: CollectionTypeEnum::Cod,
            collectedAmount: 1000,
            collectedAt: now(),
        );

        $this->assertDatabaseHas('collections', [
            'order_id' => $order->order_id,
            'collected_amount' => 1000,
            'agent_commission_amount' => 50,
            'agent_net_due' => 950,
            'system_commission_amount' => 80,
            'company_net_due' => 920,
        ]);
        $this->assertDatabaseHas('order_financials', [
            'order_id' => $order->order_id,
            'collected_amount' => 1000,
            'agent_commission_amount' => 50,
            'system_commission_amount' => 80,
            'net_due_company' => 920,
        ]);
        $this->assertSame('950.00', $agent->fresh()->balance);
        $this->assertSame('920.00', $company->fresh()->balance);

        $agent->update(['commission_value' => 500]);
        $company->update(['commission_value' => 600]);

        $service->record(
            order: $order,
            deliveryAgentId: $agent->delivery_agent_id,
            collectionType: CollectionTypeEnum::Cod,
            collectedAmount: 1100,
            collectedAt: now(),
        );

        $this->assertDatabaseHas('collections', [
            'order_id' => $order->order_id,
            'collected_amount' => 1100,
            'agent_commission_amount' => 50,
            'agent_net_due' => 1050,
            'system_commission_amount' => 80,
            'company_net_due' => 1020,
        ]);
        $this->assertSame('1050.00', $agent->fresh()->balance);
        $this->assertSame('1020.00', $company->fresh()->balance);
    }

    public function test_it_keeps_negative_balances_and_reverses_stored_signed_values(): void
    {
        [$order, $agent, $company] = $this->createOrderContext(50, 50);
        $service = app(RecordCollectionService::class);

        $service->record(
            order: $order,
            deliveryAgentId: $agent->delivery_agent_id,
            collectionType: CollectionTypeEnum::ShippingFee,
            collectedAmount: 30,
            collectedAt: now(),
        );

        $agent->refresh();
        $company->refresh();

        $this->assertSame('-20.00', $agent->balance);
        $this->assertSame('-20.00', $company->balance);
        $this->assertSame(-20.0, (float) $agent->balance);

        $service->reverse($order, now());

        $this->assertSame('0.00', $agent->fresh()->balance);
        $this->assertSame('0.00', $company->fresh()->balance);
        $this->assertDatabaseHas('collections', [
            'order_id' => $order->order_id,
            'collected_amount' => 0,
            'agent_commission_amount' => 0,
            'agent_net_due' => 0,
            'system_commission_amount' => 0,
            'company_net_due' => 0,
        ]);
    }

    public function test_mark_cash_received_does_not_change_balances(): void
    {
        Event::fake();
        [$order, $agent, $company] = $this->createOrderContext(50, 80);
        $admin = User::factory()->create([
            'user_id' => (string) Str::uuid(),
            'account_type' => AccountTypeEnum::SuperAdmin->value,
        ]);

        $result = app(RecordCollectionService::class)->record(
            order: $order,
            deliveryAgentId: $agent->delivery_agent_id,
            collectionType: CollectionTypeEnum::Cod,
            collectedAmount: 1000,
            collectedAt: now(),
        );

        app(MarkCashReceivedUseCase::class)->execute($result['collection_id'], $admin->user_id);

        $this->assertSame('950.00', $agent->fresh()->balance);
        $this->assertSame('920.00', $company->fresh()->balance);
        $this->assertDatabaseMissing('collections', [
            'collection_id' => $result['collection_id'],
            'cash_received_at' => null,
        ]);
    }

    /**
     * @return array{Order, DeliveryAgent, ShippingCompany}
     */
    private function createOrderContext(float $agentCommission, float $systemCommission): array
    {
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
            'commission_value' => $agentCommission,
            'balance' => 0,
        ]);
        $company = ShippingCompany::query()->forceCreate([
            'shipping_company_id' => (string) Str::uuid(),
            'user_id' => $companyUser->user_id,
            'company_name' => 'Acme Logistics',
            'commission_value' => $systemCommission,
            'balance' => 0,
        ]);
        $order = Order::query()->forceCreate([
            'order_id' => (string) Str::uuid(),
            'reference_no' => 'EXT-'.Str::random(8),
            'reference_code' => 'ACME-'.Str::random(8),
            'shipping_company_id' => $company->shipping_company_id,
            'delivery_agent_id' => $agent->delivery_agent_id,
            'status' => OrderStatusEnum::OutForDelivery->value,
        ]);

        DB::table('order_financials')->insert([
            'order_financial_id' => (string) Str::uuid(),
            'order_id' => $order->order_id,
            'original_amount' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$order, $agent, $company];
    }
}
