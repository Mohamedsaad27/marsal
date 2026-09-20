<?php

namespace Tests\Unit\Collections;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;
use App\Modules\Collections\Infrastructure\Database\Models\SettlementItem;
use App\Modules\Collections\Presentation\Http\Resources\Admin\AdminCollectionResource;
use App\Modules\Collections\Presentation\Http\Resources\Admin\SettlementResource;
use App\Modules\Collections\Presentation\Http\Resources\AgentCollectionListResource;
use App\Modules\Collections\Presentation\Http\Resources\Company\CompanySettlementDetailResource;
use App\Modules\Reports\Presentation\Http\Resources\CollectionsReportResource;
use App\Modules\Reports\Presentation\Http\Resources\SettlementsReportResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiResourceContractTest extends TestCase
{
    public function test_collection_resources_expose_only_explicit_financial_names(): void
    {
        $collection = $this->collectionWithSettlementStatuses();
        $request = Request::create('/');

        $admin = (new AdminCollectionResource($collection))->resolve($request);
        $agent = (new AgentCollectionListResource($collection))->resolve($request);
        $report = (new CollectionsReportResource($collection))->resolve($request);

        foreach ([$admin, $report] as $payload) {
            $this->assertArrayHasKey('agent_commission_amount', $payload);
            $this->assertArrayHasKey('agent_net_due', $payload);
            $this->assertArrayHasKey('system_commission_amount', $payload);
            $this->assertArrayHasKey('company_net_due', $payload);
            $this->assertArrayHasKey('agent_settlement_status', $payload);
            $this->assertArrayHasKey('company_settlement_status', $payload);
            $this->assertOldCollectionKeysAreAbsent($payload);
        }

        $this->assertArrayHasKey('agent_commission_amount', $agent);
        $this->assertArrayHasKey('agent_net_due', $agent);
        $this->assertArrayHasKey('agent_settlement_status', $agent);
        $this->assertOldCollectionKeysAreAbsent($agent);
    }

    public function test_settlement_resources_expose_direction_and_absolute_payable_amount(): void
    {
        $request = Request::create('/');

        foreach ([
            [SettlementTypeEnum::Agent, 950, 'agent_to_system', 950.0],
            [SettlementTypeEnum::Agent, -20, 'system_to_agent', 20.0],
            [SettlementTypeEnum::Company, 920, 'system_to_company', 920.0],
            [SettlementTypeEnum::Company, -20, 'company_to_system', 20.0],
            [SettlementTypeEnum::Company, 0, 'no_payment', 0.0],
        ] as [$type, $netAmount, $direction, $payableAmount]) {
            $settlement = $this->settlement($type, $netAmount);

            $admin = (new SettlementResource($settlement))->resolve($request);
            $report = (new SettlementsReportResource($settlement))->resolve($request);

            foreach ([$admin, $report] as $payload) {
                $this->assertSame($direction, $payload['payment_direction']);
                $this->assertSame($payableAmount, $payload['payable_amount']);
                $this->assertSame((float) $netAmount, (float) $payload['net_amount']);
            }
        }
    }

    public function test_company_settlement_collection_contract_uses_company_side_snapshot_names(): void
    {
        $settlement = $this->settlement(SettlementTypeEnum::Company, 920);
        $collection = $this->collectionWithSettlementStatuses();
        $item = new SettlementItem([
            'gross_amount' => 1000,
            'commission_amount' => 80,
            'net_amount' => 920,
        ]);
        $item->setRelation('collection', $collection);
        $settlement->setRelation('items', collect([$item]));
        $settlement->setAttribute('collections_count', 1);

        $payload = (new CompanySettlementDetailResource($settlement))->resolve(Request::create('/'));
        $nestedCollection = $payload['collections'][0];

        $this->assertArrayHasKey('system_commission_amount', $nestedCollection);
        $this->assertArrayHasKey('company_net_due', $nestedCollection);
        $this->assertArrayHasKey('company_settlement_status', $nestedCollection);
        $this->assertOldCollectionKeysAreAbsent($nestedCollection);
    }

    private function collectionWithSettlementStatuses(): Collection
    {
        $collection = new Collection([
            'collection_id' => 'collection-1',
            'order_id' => 'order-1',
            'collection_type' => CollectionTypeEnum::Cod->value,
            'collected_amount' => 1000,
            'agent_commission_amount' => 50,
            'agent_net_due' => 950,
            'system_commission_amount' => 80,
            'company_net_due' => 920,
            'collected_at' => Carbon::parse('2026-09-20 10:00:00'),
        ]);

        $agentItem = new SettlementItem;
        $agentItem->setRelation(
            'settlement',
            $this->settlement(SettlementTypeEnum::Agent, 950, SettlementStatusEnum::Paid),
        );
        $companyItem = new SettlementItem;
        $companyItem->setRelation(
            'settlement',
            $this->settlement(SettlementTypeEnum::Company, 920, SettlementStatusEnum::Draft),
        );

        $collection->setRelation('agentSettlementItem', $agentItem);
        $collection->setRelation('companySettlementItem', $companyItem);

        return $collection;
    }

    private function settlement(
        SettlementTypeEnum $type,
        float $netAmount,
        SettlementStatusEnum $status = SettlementStatusEnum::Paid,
    ): Settlement {
        $settlement = new Settlement([
            'settlement_id' => 'settlement-'.$type->value.'-'.$netAmount,
            'settlement_type' => $type->value,
            'settlement_status' => $status->value,
            'total_collections' => 1000,
            'total_commissions' => 80,
            'net_amount' => $netAmount,
            'period_from' => '2026-09-01',
            'period_to' => '2026-09-30',
        ]);
        $settlement->setAttribute('collections_count', 1);
        $settlement->setAttribute('eligible_collections_count', 1);

        return $settlement;
    }

    private function assertOldCollectionKeysAreAbsent(array $payload): void
    {
        $this->assertArrayNotHasKey('commission_amount', $payload);
        $this->assertArrayNotHasKey('net_due', $payload);
        $this->assertArrayNotHasKey('settlement_id', $payload);
    }
}
