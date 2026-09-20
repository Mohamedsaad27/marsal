<?php

namespace App\Modules\Orders\Domain\Services;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordCollectionService
{
    public function __construct(
        private CommissionCalculatorService $commissionCalculator,
    ) {}

    /**
     * @return array{collection_id: string, collected_amount: string}
     */
    public function record(
        Order $order,
        string $deliveryAgentId,
        CollectionTypeEnum $collectionType,
        float $collectedAmount,
        DateTimeInterface $collectedAt,
        ?float $approvedAmount = null,
    ): array {
        return DB::transaction(function () use (
            $order,
            $deliveryAgentId,
            $collectionType,
            $collectedAmount,
            $collectedAt,
            $approvedAmount,
        ): array {
            $existingCollection = DB::table('collections')
                ->where('order_id', $order->order_id)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            $agent = DeliveryAgent::query()
                ->whereKey($deliveryAgentId)
                ->lockForUpdate()
                ->firstOrFail();

            $company = ShippingCompany::query()
                ->whereKey($order->shipping_company_id)
                ->lockForUpdate()
                ->firstOrFail();

            $agentCommissionValue = $existingCollection === null
                ? (float) $agent->commission_value
                : (float) $existingCollection->agent_commission_amount;
            $systemCommissionValue = $existingCollection === null
                ? (float) $company->commission_value
                : (float) $existingCollection->system_commission_amount;

            $financials = $this->commissionCalculator->calculateForCollection(
                collectedAmount: $collectedAmount,
                agentCommissionValue: $agentCommissionValue,
                systemCommissionValue: $systemCommissionValue,
            );

            $previousAgentNetDue = (float) ($existingCollection->agent_net_due ?? 0);
            $previousCompanyNetDue = (float) ($existingCollection->company_net_due ?? 0);
            $collectionId = $existingCollection->collection_id ?? (string) Str::uuid();

            $attributes = [
                'delivery_agent_id' => $deliveryAgentId,
                'shipping_company_id' => $order->shipping_company_id,
                'collection_type' => $collectionType->value,
                'collected_amount' => round($collectedAmount, 2),
                'agent_commission_amount' => $financials['agent_commission_amount'],
                'agent_net_due' => $financials['agent_net_due'],
                'system_commission_amount' => $financials['system_commission_amount'],
                'company_net_due' => $financials['company_net_due'],
                'cash_received_at' => null,
                'cash_received_by' => null,
                'collected_at' => $collectedAt,
                'updated_at' => $collectedAt,
            ];

            if ($existingCollection === null) {
                DB::table('collections')->insert(array_merge($attributes, [
                    'collection_id' => $collectionId,
                    'order_id' => $order->order_id,
                    'created_at' => $collectedAt,
                ]));
            } else {
                DB::table('collections')
                    ->where('collection_id', $collectionId)
                    ->update($attributes);
            }

            $agentBalanceDelta = round($financials['agent_net_due'] - $previousAgentNetDue, 2);
            $companyBalanceDelta = round($financials['company_net_due'] - $previousCompanyNetDue, 2);

            if ($agentBalanceDelta !== 0.0) {
                $agent->increment('balance', $agentBalanceDelta);
            }

            if ($companyBalanceDelta !== 0.0) {
                $company->increment('balance', $companyBalanceDelta);
            }

            $orderFinancialAttributes = [
                'collected_amount' => round($collectedAmount, 2),
                'agent_commission_amount' => $financials['agent_commission_amount'],
                'system_commission_amount' => $financials['system_commission_amount'],
                'net_due_company' => $financials['company_net_due'],
                'updated_at' => $collectedAt,
            ];

            if ($approvedAmount !== null) {
                $orderFinancialAttributes['approved_amount'] = round($approvedAmount, 2);
            }

            DB::table('order_financials')
                ->where('order_id', $order->order_id)
                ->update($orderFinancialAttributes);

            return [
                'collection_id' => $collectionId,
                'collected_amount' => number_format($collectedAmount, 2, '.', ''),
            ];
        });
    }

    public function reverse(Order $order, DateTimeInterface $reversedAt): void
    {
        DB::transaction(function () use ($order, $reversedAt): void {
            $collection = DB::table('collections')
                ->where('order_id', $order->order_id)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if ($collection !== null) {
                if ($collection->delivery_agent_id !== null) {
                    $agent = DeliveryAgent::query()
                        ->whereKey($collection->delivery_agent_id)
                        ->lockForUpdate()
                        ->first();

                    $agent?->increment('balance', -round((float) $collection->agent_net_due, 2));
                }

                if ($collection->shipping_company_id !== null) {
                    $company = ShippingCompany::query()
                        ->whereKey($collection->shipping_company_id)
                        ->lockForUpdate()
                        ->first();

                    $company?->increment('balance', -round((float) $collection->company_net_due, 2));
                }

                DB::table('collections')
                    ->where('collection_id', $collection->collection_id)
                    ->update([
                        'collected_amount' => 0,
                        'agent_commission_amount' => 0,
                        'agent_net_due' => 0,
                        'system_commission_amount' => 0,
                        'company_net_due' => 0,
                        'cash_received_at' => null,
                        'cash_received_by' => null,
                        'updated_at' => $reversedAt,
                    ]);
            }

            DB::table('order_financials')
                ->where('order_id', $order->order_id)
                ->update([
                    'collected_amount' => 0,
                    'agent_commission_amount' => 0,
                    'system_commission_amount' => 0,
                    'net_due_company' => 0,
                    'updated_at' => $reversedAt,
                ]);
        });
    }
}
