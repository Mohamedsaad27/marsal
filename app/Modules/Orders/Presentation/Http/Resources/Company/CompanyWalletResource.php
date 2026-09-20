<?php

namespace App\Modules\Orders\Presentation\Http\Resources\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyWalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lastSettlement = $this->resource['last_settlement'];
        $balance = round((float) $this->resource['balance'], 2);

        return [
            'balance' => $balance,
            'balance_direction' => $balance > 0
                ? 'system_owes_company'
                : ($balance < 0 ? 'company_owes_system' : 'settled'),
            'balance_amount' => abs($balance),
            'total_collected' => (float) $this->resource['total_collected'],
            'total_commissions' => (float) $this->resource['total_commissions'],
            'total_net_due' => (float) $this->resource['total_net_due'],
            'amount_payable_to_company' => (float) $this->resource['amount_payable_to_company'],
            'amount_receivable_from_company' => (float) $this->resource['amount_receivable_from_company'],
            'pending_settlement_amount' => (float) $this->resource['pending_settlement_amount'],
            'pending_collection_count' => (int) $this->resource['pending_collection_count'],
            'last_settlement' => $lastSettlement !== null ? [
                'reference' => $lastSettlement['reference'],
                'net_amount' => (float) $lastSettlement['net_amount'],
                'payment_direction' => $lastSettlement['payment_direction'],
                'payable_amount' => (float) $lastSettlement['payable_amount'],
                'paid_at' => $lastSettlement['paid_at'],
            ] : null,
        ];
    }
}
