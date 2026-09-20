<?php

namespace App\Modules\Dashboard\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionsBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'signed_company_balance' => $this->resource['signed_company_balance'],
            'system_payable_to_companies' => $this->resource['system_payable_to_companies'],
            'companies_payable_to_system' => $this->resource['companies_payable_to_system'],
            'currency' => $this->resource['currency'],
            'creditor_company_count' => $this->resource['creditor_company_count'],
            'debtor_company_count' => $this->resource['debtor_company_count'],
        ];
    }
}
