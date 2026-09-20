<?php

namespace Tests\Feature\Collections;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Collections\Domain\Interfaces\SettlementRepositoryInterface;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;
use App\Modules\Dashboard\Application\Queries\GetCollectionsBalanceQuery;
use App\Modules\Orders\Domain\Interfaces\CompanyOrderRepositoryInterface;
use App\Modules\Reports\Application\DTOs\ReportFilterDTO;
use App\Modules\Reports\Domain\Interfaces\ReportsRepositoryInterface;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use App\Modules\Users\Infrastructure\Database\Models\User;
use App\Modules\Users\Infrastructure\Persistence\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinancialReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_and_company_wallet_keep_positive_and_negative_exposure_separate(): void
    {
        [$admin, $agent, $company] = $this->createActors();
        $this->createCollection($agent, $company, 120, 20, 40);
        $this->createCollection($agent, $company, 20, 40, 50);

        $collectionsReport = app(ReportsRepositoryInterface::class)->collections(new ReportFilterDTO);
        $summary = $collectionsReport['summary'];

        $this->assertSame('80.00', $summary['total_agent_net_due']);
        $this->assertSame('100.00', $summary['agent_to_system_amount']);
        $this->assertSame('20.00', $summary['system_to_agent_amount']);
        $this->assertSame('50.00', $summary['total_company_net_due']);
        $this->assertSame('80.00', $summary['system_to_company_amount']);
        $this->assertSame('30.00', $summary['company_to_system_amount']);

        $wallet = app(CompanyOrderRepositoryInterface::class)
            ->getWalletAggregates($company->shipping_company_id);

        $this->assertSame(90.0, $wallet['total_commissions']);
        $this->assertSame(50.0, $wallet['total_net_due']);
        $this->assertSame(80.0, $wallet['amount_payable_to_company']);
        $this->assertSame(30.0, $wallet['amount_receivable_from_company']);
        $this->assertSame(50.0, $wallet['pending_settlement_amount']);
        $this->assertSame(2, $wallet['pending_collection_count']);

        $this->createSettlement($admin, $agent, $company, SettlementTypeEnum::Agent, 100);
        $this->createSettlement($admin, $agent, $company, SettlementTypeEnum::Agent, -20);
        $this->createSettlement($admin, $agent, $company, SettlementTypeEnum::Company, 80);
        $this->createSettlement($admin, $agent, $company, SettlementTypeEnum::Company, -30);

        $settlementSummary = app(ReportsRepositoryInterface::class)
            ->settlements(new ReportFilterDTO)['summary'];

        $this->assertSame('100.00', $settlementSummary['agent_to_system_amount']);
        $this->assertSame('20.00', $settlementSummary['system_to_agent_amount']);
        $this->assertSame('80.00', $settlementSummary['system_to_company_amount']);
        $this->assertSame('30.00', $settlementSummary['company_to_system_amount']);

        $stats = app(SettlementRepositoryInterface::class)->stats()['all'];
        $this->assertSame('100.00', $stats['agent_to_system_amount']);
        $this->assertSame('20.00', $stats['system_to_agent_amount']);
        $this->assertSame('80.00', $stats['system_to_company_amount']);
        $this->assertSame('30.00', $stats['company_to_system_amount']);
    }

    public function test_dashboard_and_deletion_guards_preserve_negative_balances(): void
    {
        [, $agent, $company] = $this->createActors();
        $agent->update(['balance' => -25]);
        $company->update(['balance' => 80]);

        $debtorUser = User::factory()->create([
            'user_id' => (string) Str::uuid(),
            'account_type' => AccountTypeEnum::ShippingCompany->value,
        ]);
        $debtorCompany = ShippingCompany::query()->forceCreate([
            'shipping_company_id' => (string) Str::uuid(),
            'user_id' => $debtorUser->user_id,
            'company_name' => 'Debtor Logistics',
            'commission_value' => 0,
            'balance' => -30,
        ]);

        $dashboard = app(GetCollectionsBalanceQuery::class)->execute();

        $this->assertSame(50.0, $dashboard['signed_company_balance']);
        $this->assertSame(80.0, $dashboard['system_payable_to_companies']);
        $this->assertSame(30.0, $dashboard['companies_payable_to_system']);
        $this->assertSame(1, $dashboard['creditor_company_count']);
        $this->assertSame(1, $dashboard['debtor_company_count']);

        $users = app(UserRepository::class);
        $this->assertTrue($users->deliveryAgentHasNonZeroBalance($agent->delivery_agent_id));
        $this->assertTrue($users->shippingCompanyHasNonZeroBalance($debtorCompany->shipping_company_id));
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
            'commission_value' => 0,
            'balance' => 0,
        ]);
        $company = ShippingCompany::query()->forceCreate([
            'shipping_company_id' => (string) Str::uuid(),
            'user_id' => $companyUser->user_id,
            'company_name' => 'Acme Logistics',
            'commission_value' => 0,
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
    ): Collection {
        return Collection::query()->forceCreate([
            'collection_id' => (string) Str::uuid(),
            'delivery_agent_id' => $agent->delivery_agent_id,
            'shipping_company_id' => $company->shipping_company_id,
            'collection_type' => CollectionTypeEnum::Cod->value,
            'collected_amount' => $collectedAmount,
            'agent_commission_amount' => $agentCommission,
            'agent_net_due' => $collectedAmount - $agentCommission,
            'system_commission_amount' => $systemCommission,
            'company_net_due' => $collectedAmount - $systemCommission,
            'collected_at' => now(),
        ]);
    }

    private function createSettlement(
        User $admin,
        DeliveryAgent $agent,
        ShippingCompany $company,
        SettlementTypeEnum $type,
        float $netAmount,
    ): Settlement {
        return Settlement::query()->forceCreate([
            'settlement_id' => (string) Str::uuid(),
            'settlement_type' => $type->value,
            'settlement_status' => SettlementStatusEnum::Draft->value,
            'delivery_agent_id' => $type === SettlementTypeEnum::Agent ? $agent->delivery_agent_id : null,
            'shipping_company_id' => $type === SettlementTypeEnum::Company ? $company->shipping_company_id : null,
            'initiated_by' => $admin->user_id,
            'total_collections' => abs($netAmount),
            'total_commissions' => 0,
            'net_amount' => $netAmount,
            'period_from' => now()->subDay()->toDateString(),
            'period_to' => now()->toDateString(),
        ]);
    }
}
