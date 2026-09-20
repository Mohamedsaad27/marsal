<?php

namespace Tests\Feature\Collections;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Roles\Infrastructure\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use App\Modules\Users\Infrastructure\Database\Models\User;
use App\Modules\Users\Infrastructure\Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CollectionSettlementApiLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $this->admin = User::query()->where('email', env('SUPER_ADMIN_EMAIL', 'superadmin@marsal.com'))->firstOrFail();
    }

    public function test_postman_collection_cash_agent_and_company_settlement_cycle_for_same_collection(): void
    {
        [$agent, $agentUser, $company, $companyUser, $order] = $this->createAssignedOrder();

        $this->actingAs($agentUser, 'api')
            ->patchJson("/api/v1/agent/orders/{$order->order_id}/status", [
                'status_id' => OrderStatusEnum::OutForDelivery->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.collection_created', false);

        $this->actingAs($agentUser, 'api')
            ->patchJson("/api/v1/agent/orders/{$order->order_id}/status", [
                'status_id' => OrderStatusEnum::Delivered->value,
                'collection_type' => CollectionTypeEnum::Cod->value,
                'collected_amount' => 1000,
            ])
            ->assertOk()
            ->assertJsonPath('data.collection_created', true);

        $collection = Collection::query()->where('order_id', $order->order_id)->firstOrFail();

        $adminCollection = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/collections?per_page=100')
            ->assertOk()
            ->collect('data.items')
            ->firstWhere('id', $collection->collection_id);

        $this->assertIsArray($adminCollection);
        $this->assertArrayHasKey('agent_commission_amount', $adminCollection);
        $this->assertArrayHasKey('agent_net_due', $adminCollection);
        $this->assertArrayHasKey('system_commission_amount', $adminCollection);
        $this->assertArrayHasKey('company_net_due', $adminCollection);
        $this->assertArrayHasKey('agent_settlement_status', $adminCollection);
        $this->assertArrayHasKey('company_settlement_status', $adminCollection);
        $this->assertArrayNotHasKey('commission_amount', $adminCollection);
        $this->assertArrayNotHasKey('net_due', $adminCollection);
        $this->assertArrayNotHasKey('settlement_id', $adminCollection);

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/collections/{$collection->collection_id}/mark-cash-received")
            ->assertOk()
            ->assertJsonPath('data.id', $collection->collection_id)
            ->assertJsonPath('data.agent_net_due', '950.00')
            ->assertJsonPath('data.company_net_due', '920.00');

        $agentSettlementId = $this->createApproveAndPaySettlement(
            SettlementTypeEnum::Agent,
            $agent->delivery_agent_id,
            'agent_to_system',
            950,
        );

        $this->assertDatabaseHas('order_financials', [
            'order_id' => $order->order_id,
            'is_settled' => false,
        ]);

        $companySettlementId = $this->createApproveAndPaySettlement(
            SettlementTypeEnum::Company,
            $company->shipping_company_id,
            'system_to_company',
            920,
        );

        $this->assertDatabaseHas('settlement_items', [
            'settlement_id' => $agentSettlementId,
            'collection_id' => $collection->collection_id,
            'settlement_type' => SettlementTypeEnum::Agent->value,
            'net_amount' => 950,
        ]);
        $this->assertDatabaseHas('settlement_items', [
            'settlement_id' => $companySettlementId,
            'collection_id' => $collection->collection_id,
            'settlement_type' => SettlementTypeEnum::Company->value,
            'net_amount' => 920,
        ]);
        $this->assertDatabaseHas('order_financials', [
            'order_id' => $order->order_id,
            'collected_amount' => 1000,
            'agent_commission_amount' => 50,
            'system_commission_amount' => 80,
            'net_due_company' => 920,
            'is_settled' => true,
        ]);

        $this->actingAs($companyUser, 'api')
            ->getJson('/api/v1/company/wallet')
            ->assertOk()
            ->assertJsonPath('data.balance', 0)
            ->assertJsonPath('data.last_settlement.payment_direction', 'system_to_company')
            ->assertJsonPath('data.last_settlement.payable_amount', 920);
    }

    private function createApproveAndPaySettlement(
        SettlementTypeEnum $type,
        string $referenceEntityId,
        string $expectedDirection,
        int $expectedPayable,
    ): string {
        $create = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/settlements', [
                'settlement_type' => $type->value,
                'reference_entity_id' => $referenceEntityId,
                'period_from' => now()->subDay()->toDateString(),
                'period_to' => now()->addDay()->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.status.code', SettlementStatusEnum::Draft->value)
            ->assertJsonPath('data.payment_direction', $expectedDirection)
            ->assertJsonPath('data.payable_amount', $expectedPayable);

        $settlementId = $create->json('data.id');

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/settlements/{$settlementId}/approve")
            ->assertOk()
            ->assertJsonPath('data.status.code', SettlementStatusEnum::Approved->value)
            ->assertJsonPath('data.payment_direction', $expectedDirection)
            ->assertJsonPath('data.payable_amount', $expectedPayable);

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/settlements/{$settlementId}/mark-paid", [
                'payment_method' => 'cash',
                'payment_reference' => 'POSTMAN-'.$type->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.status.code', SettlementStatusEnum::Paid->value)
            ->assertJsonPath('data.payment_direction', $expectedDirection)
            ->assertJsonPath('data.payable_amount', $expectedPayable);

        return $settlementId;
    }

    /**
     * @return array{DeliveryAgent, User, ShippingCompany, User, Order}
     */
    private function createAssignedOrder(): array
    {
        $agentUser = User::factory()->create([
            'user_id' => (string) Str::uuid(),
            'account_type' => AccountTypeEnum::DeliveryAgent->value,
        ]);
        $agentUser->assignRole('delivery_agent');

        $companyUser = User::factory()->create([
            'user_id' => (string) Str::uuid(),
            'account_type' => AccountTypeEnum::ShippingCompany->value,
        ]);
        $companyUser->assignRole('shipping_company');

        $agent = DeliveryAgent::query()->forceCreate([
            'delivery_agent_id' => (string) Str::uuid(),
            'user_id' => $agentUser->user_id,
            'commission_value' => 50,
            'balance' => 0,
        ]);
        $company = ShippingCompany::query()->forceCreate([
            'shipping_company_id' => (string) Str::uuid(),
            'user_id' => $companyUser->user_id,
            'company_name' => 'API Cycle Logistics',
            'commission_value' => 80,
            'balance' => 0,
        ]);

        $order = Order::query()->forceCreate([
            'order_id' => (string) Str::uuid(),
            'reference_no' => 'POSTMAN-'.Str::upper(Str::random(8)),
            'reference_code' => 'POSTMAN-'.Str::upper(Str::random(8)),
            'shipping_company_id' => $company->shipping_company_id,
            'delivery_agent_id' => $agent->delivery_agent_id,
            'status' => OrderStatusEnum::Assigned->value,
            'assigned_at' => now(),
        ]);

        DB::table('order_financials')->insert([
            'order_financial_id' => (string) Str::uuid(),
            'order_id' => $order->order_id,
            'original_amount' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            $agent,
            $agentUser,
            $company,
            $companyUser,
            $order,
        ];
    }
}
