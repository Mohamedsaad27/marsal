<?php

namespace App\Modules\Orders\Presentation\Http\Resources\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyWalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lastSettlement = $this->resource['last_settlement'];

        return [
            'balance'                   => (float) $this->resource['balance'],
            'total_collected'           => (float) $this->resource['total_collected'],
            'total_commissions'         => (float) $this->resource['total_commissions'],
            'total_net_due'             => (float) $this->resource['total_net_due'],
            'pending_settlement_amount' => (float) $this->resource['pending_settlement_amount'],
            'pending_collection_count'  => (int) $this->resource['pending_collection_count'],
            'last_settlement'           => $lastSettlement !== null ? [
                'reference'  => $lastSettlement['reference'],
                'net_amount' => (float) $lastSettlement['net_amount'],
                'paid_at'    => $lastSettlement['paid_at'],
            ] : null,
        ];
    }
}
