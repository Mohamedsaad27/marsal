<?php

namespace Tests\Feature\Collections;

use App\Modules\Collections\Application\DTOs\CreateSettlementDTO;
use App\Modules\Collections\Application\Exceptions\NoCollectionsFoundForPeriodException;
use App\Modules\Collections\Application\UseCases\Admin\ApproveSettlementUseCase;
use App\Modules\Collections\Application\UseCases\Admin\CreateSettlementUseCase;
use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;
use App\Modules\Collections\Infrastructure\Database\Models\SettlementItem;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettlementDraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
    }

    public function test_agent_and_company_drafts_support_signed_eligibility_and_independent_items(): void
    {
        [$admin, $agent, $company] = $this->createActors();
        $receivedPositive = $this->createCollection($agent, $company, 100, 20, 20, true);
        $zero = $this->createCollection($agent, $company, 20, 20, 20, false);
        $negative = $this->createCollection($agent, $company, 10, 20, 20, false);
        $unreceivedPositive = $this->createCollection($agent, $company, 100, 20, 20, false);

        $agentSettlement = $this->createSettlement(
            SettlementTypeEnum::Agent,
            $agent->delivery_agent_id,
            $admin->user_id,
        );
        $companySettlement = $this->createSettlement(
            SettlementTypeEnum::Company,
            $company->shipping_company_id,
            $admin->user_id,
        );

        foreach ([$agentSettlement, $companySettlement] as $settlement) {
            $this->assertSame('130.00', $settlement->total_collections);
            $this->assertSame('60.00', $settlement->total_commissions);
            $this->assertSame('70.00', $settlement->net_amount);
            $this->assertSame(3, $settlement->items->count());
        }

        foreach ([$receivedPositive, $zero, $negative] as $collection) {
            $this->assertDatabaseHas('settlement_items', [
                'collection_id' => $collection->collection_id,
                'settlement_type' => SettlementTypeEnum::Agent->value,
            ]);
            $this->assertDatabaseHas('settlement_items', [
                'collection_id' => $collection->collection_id,
                'settlement_type' => SettlementTypeEnum::Company->value,
            ]);
        }

        $this->assertDatabaseMissing('settlement_items', [
            'collection_id' => $unreceivedPositive->collection_id,
        ]);
        $this->assertSame(2, SettlementItem::query()
            ->where('collection_id', $receivedPositive->collection_id)
            ->count());
    }

    public function test_draft_and_approval_keep_item_snapshots_immutable(): void
    {
        [$admin, $agent, $company] = $this->createActors();
        $collection = $this->createCollection($agent, $company, 1000, 50, 80, true);
        $settlement = $this->createSettlement(
            SettlementTypeEnum::Agent,
            $agent->delivery_agent_id,
            $admin->user_id,
        );

        $collection->update([
            'collected_amount' => 2000,
            'agent_commission_amount' => 500,
            'agent_net_due' => 1500,
        ]);
        $newCollection = $this->createCollection($agent, $company, 300, 50, 80, true);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $approved = app(ApproveSettlementUseCase::class)->execute($settlement->settlement_id);
        $approvalQueries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame(SettlementStatusEnum::Approved, $approved->settlement_status);
        $this->assertSame('1000.00', $approved->total_collections);
        $this->assertSame('50.00', $approved->total_commissions);
        $this->assertSame('950.00', $approved->net_amount);
        $this->assertCount(1, $approved->items);
        $this->assertSame('1000.00', $approved->items->first()->gross_amount);
        $this->assertSame('50.00', $approved->items->first()->commission_amount);
        $this->assertSame('950.00', $approved->items->first()->net_amount);
        $this->assertDatabaseMissing('settlement_items', [
            'settlement_id' => $settlement->settlement_id,
            'collection_id' => $newCollection->collection_id,
        ]);
        $this->assertFalse(collect($approvalQueries)->contains(
            fn (array $query): bool => str_contains(strtolower($query['query']), 'from `collections`'),
        ));
    }

    public function test_collection_cannot_enter_two_drafts_of_the_same_type(): void
    {
        [$admin, $agent, $company] = $this->createActors();
        $this->createCollection($agent, $company, 1000, 50, 80, true);

        $this->createSettlement(
            SettlementTypeEnum::Agent,
            $agent->delivery_agent_id,
            $admin->user_id,
        );

        $this->expectException(NoCollectionsFoundForPeriodException::class);

        $this->createSettlement(
            SettlementTypeEnum::Agent,
            $agent->delivery_agent_id,
            $admin->user_id,
        );
    }

    public function test_lock_and_unique_constraint_guard_concurrent_duplicate_reservations(): void
    {
        [$admin, $agent, $company] = $this->createActors();
        $collection = $this->createCollection($agent, $company, 1000, 50, 80, true);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $settlement = $this->createSettlement(
            SettlementTypeEnum::Agent,
            $agent->delivery_agent_id,
            $admin->user_id,
        );
        $creationQueries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertTrue(collect($creationQueries)->contains(
            fn (array $query): bool => str_contains(strtolower($query['query']), 'for update'),
        ));

        $competingSettlement = Settlement::query()->forceCreate([
            'settlement_id' => (string) Str::uuid(),
            'settlement_type' => SettlementTypeEnum::Agent->value,
            'settlement_status' => SettlementStatusEnum::Draft->value,
            'delivery_agent_id' => $agent->delivery_agent_id,
            'initiated_by' => $admin->user_id,
            'total_collections' => 1000,
            'total_commissions' => 50,
            'net_amount' => 950,
            'period_from' => now()->subDay()->toDateString(),
            'period_to' => now()->addDay()->toDateString(),
        ]);

        $this->expectException(QueryException::class);

        SettlementItem::query()->create([
            'settlement_id' => $competingSettlement->settlement_id,
            'collection_id' => $collection->collection_id,
            'settlement_type' => SettlementTypeEnum::Agent->value,
            'gross_amount' => 1000,
            'commission_amount' => 50,
            'net_amount' => 950,
        ]);
    }

    private function createSettlement(
        SettlementTypeEnum $type,
        string $referenceEntityId,
        string $initiatedBy,
    ): Settlement {
        return app(CreateSettlementUseCase::class)->execute(new CreateSettlementDTO(
            settlementType: $type,
            referenceEntityId: $referenceEntityId,
            periodFrom: now()->subDay()->toDateString(),
            periodTo: now()->addDay()->toDateString(),
            initiatedBy: $initiatedBy,
        ));
    }

    /**
     * @return array{User, DeliveryAgent, ShippingCompany}
     */
    private function createActors(): array
    {
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
            'commission_value' => 50,
            'balance' => 0,
        ]);
        $company = ShippingCompany::query()->forceCreate([
            'shipping_company_id' => (string) Str::uuid(),
            'user_id' => $companyUser->user_id,
            'company_name' => 'Acme Logistics',
            'commission_value' => 80,
            'balance' => 0,
        ]);

        return [$admin, $agent, $company];
    }

    private function createCollection(
        DeliveryAgent $agent,
        ShippingCompany $company,
        float $collectedAmount,
        float $agentCommission,
        float $systemCommission,
        bool $cashReceived,
    ): Collection {
        $order = Order::query()->forceCreate([
            'order_id' => (string) Str::uuid(),
            'reference_no' => 'EXT-'.Str::random(8),
            'reference_code' => 'ACME-'.Str::random(8),
            'shipping_company_id' => $company->shipping_company_id,
            'delivery_agent_id' => $agent->delivery_agent_id,
            'status' => OrderStatusEnum::Delivered->value,
            'delivered_at' => now(),
        ]);

        return Collection::query()->forceCreate([
            'collection_id' => (string) Str::uuid(),
            'order_id' => $order->order_id,
            'delivery_agent_id' => $agent->delivery_agent_id,
            'shipping_company_id' => $company->shipping_company_id,
            'collection_type' => CollectionTypeEnum::Cod->value,
            'collected_amount' => $collectedAmount,
            'agent_commission_amount' => $agentCommission,
            'agent_net_due' => $collectedAmount - $agentCommission,
            'system_commission_amount' => $systemCommission,
            'company_net_due' => $collectedAmount - $systemCommission,
            'cash_received_at' => $cashReceived ? now() : null,
            'collected_at' => now(),
        ]);
    }
}
