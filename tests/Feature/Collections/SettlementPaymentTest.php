<?php

namespace Tests\Feature\Collections;

use App\Modules\Collections\Application\DTOs\CreateSettlementDTO;
use App\Modules\Collections\Application\UseCases\Admin\ApproveSettlementUseCase;
use App\Modules\Collections\Application\UseCases\Admin\CreateSettlementUseCase;
use App\Modules\Collections\Application\UseCases\Admin\MarkCashReceivedUseCase;
use App\Modules\Collections\Application\UseCases\Admin\MarkSettlementPaidUseCase;
use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;
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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SettlementPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
    }

    public static function signedPaymentScenarios(): array
    {
        return [
            'agent positive' => [SettlementTypeEnum::Agent, 100, 20, 100, 80, 'agent_to_system', 80],
            'agent negative' => [SettlementTypeEnum::Agent, 30, 50, 30, -20, 'system_to_agent', 20],
            'agent zero' => [SettlementTypeEnum::Agent, 50, 50, 50, 0, 'no_payment', 0],
            'company positive' => [SettlementTypeEnum::Company, 100, 100, 20, 80, 'system_to_company', 80],
            'company negative' => [SettlementTypeEnum::Company, 30, 30, 50, -20, 'company_to_system', 20],
            'company zero' => [SettlementTypeEnum::Company, 50, 50, 50, 0, 'no_payment', 0],
        ];
    }

    #[DataProvider('signedPaymentScenarios')]
    public function test_signed_payment_lifecycle_for_both_settlement_types(
        SettlementTypeEnum $type,
        float $collectedAmount,
        float $agentCommission,
        float $systemCommission,
        float $expectedNet,
        string $expectedDirection,
        float $expectedPayable,
    ): void {
        [$admin, $agent, $company, $order, $collectionId] = $this->createCollectedOrder(
            $collectedAmount,
            $agentCommission,
            $systemCommission,
        );

        $balanceBeforeCash = $type === SettlementTypeEnum::Agent
            ? $agent->fresh()->balance
            : $company->fresh()->balance;

        if ($expectedNet > 0) {
            app(MarkCashReceivedUseCase::class)->execute($collectionId, $admin->user_id);

            $this->assertSame(
                $balanceBeforeCash,
                $type === SettlementTypeEnum::Agent
                    ? $agent->fresh()->balance
                    : $company->fresh()->balance,
            );
        }

        $settlement = $this->createAndApproveSettlement(
            $type,
            $type === SettlementTypeEnum::Agent
                ? $agent->delivery_agent_id
                : $company->shipping_company_id,
            $admin->user_id,
        );

        $paid = app(MarkSettlementPaidUseCase::class)->execute(
            settlementId: $settlement->settlement_id,
            paymentMethod: 'cash',
            paymentReference: 'PAY-001',
            notes: null,
        );

        $this->assertSame(SettlementStatusEnum::Paid, $paid->settlement_status);
        $this->assertSame($expectedNet, (float) $paid->net_amount);
        $this->assertSame($expectedDirection, $paid->paymentDirection());
        $this->assertSame($expectedPayable, $paid->payableAmount());
        $this->assertSame(
            '0.00',
            $type === SettlementTypeEnum::Agent
                ? $agent->fresh()->balance
                : $company->fresh()->balance,
        );
        $this->assertSame(
            $expectedNet === 0.0 ? 'no_payment' : 'cash',
            $paid->payment_method,
        );
        $this->assertSame($expectedNet === 0.0 ? null : 'PAY-001', $paid->payment_reference);
        $this->assertNotNull($paid->paid_at);
        $this->assertDatabaseHas('order_financials', [
            'order_id' => $order->order_id,
            'is_settled' => false,
        ]);
    }

    public function test_order_is_settled_only_after_both_paid_without_cash_double_deduction(): void
    {
        [$admin, $agent, $company, $order, $collectionId] = $this->createCollectedOrder(1000, 50, 80);

        $this->assertSame('950.00', $agent->fresh()->balance);
        $this->assertSame('920.00', $company->fresh()->balance);

        app(MarkCashReceivedUseCase::class)->execute($collectionId, $admin->user_id);

        $this->assertSame('950.00', $agent->fresh()->balance);
        $this->assertSame('920.00', $company->fresh()->balance);

        $agentSettlement = $this->createAndApproveSettlement(
            SettlementTypeEnum::Agent,
            $agent->delivery_agent_id,
            $admin->user_id,
        );
        $companySettlement = $this->createAndApproveSettlement(
            SettlementTypeEnum::Company,
            $company->shipping_company_id,
            $admin->user_id,
        );

        Collection::query()->where('collection_id', $collectionId)->update([
            'collected_amount' => 9999,
            'agent_net_due' => 1,
            'company_net_due' => 1,
        ]);

        app(MarkSettlementPaidUseCase::class)->execute(
            settlementId: $agentSettlement->settlement_id,
            paymentMethod: 'cash',
            paymentReference: 'AGENT-PAY',
            notes: null,
        );

        $this->assertSame('0.00', $agent->fresh()->balance);
        $this->assertSame('920.00', $company->fresh()->balance);
        $this->assertDatabaseHas('order_financials', [
            'order_id' => $order->order_id,
            'is_settled' => false,
        ]);

        app(MarkSettlementPaidUseCase::class)->execute(
            settlementId: $companySettlement->settlement_id,
            paymentMethod: 'bank_transfer',
            paymentReference: 'COMPANY-PAY',
            notes: null,
        );

        $this->assertSame('0.00', $company->fresh()->balance);
        $this->assertDatabaseHas('order_financials', [
            'order_id' => $order->order_id,
            'is_settled' => true,
        ]);
    }

    private function createAndApproveSettlement(
        SettlementTypeEnum $type,
        string $referenceEntityId,
        string $initiatedBy,
    ): Settlement {
        $settlement = app(CreateSettlementUseCase::class)->execute(new CreateSettlementDTO(
            settlementType: $type,
            referenceEntityId: $referenceEntityId,
            periodFrom: now()->subDay()->toDateString(),
            periodTo: now()->addDay()->toDateString(),
            initiatedBy: $initiatedBy,
        ));

        return app(ApproveSettlementUseCase::class)->execute($settlement->settlement_id);
    }

    /**
     * @return array{User, DeliveryAgent, ShippingCompany, Order, string}
     */
    private function createCollectedOrder(
        float $collectedAmount,
        float $agentCommission,
        float $systemCommission,
    ): array {
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
            'original_amount' => $collectedAmount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $collection = app(RecordCollectionService::class)->record(
            order: $order,
            deliveryAgentId: $agent->delivery_agent_id,
            collectionType: CollectionTypeEnum::Cod,
            collectedAmount: $collectedAmount,
            collectedAt: now(),
        );

        return [$admin, $agent, $company, $order, $collection['collection_id']];
    }
}
