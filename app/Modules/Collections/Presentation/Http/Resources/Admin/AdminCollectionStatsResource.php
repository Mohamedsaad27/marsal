<?php

namespace App\Modules\Collections\Presentation\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCollectionStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_collected' => $this->resource['total_collected'],
            'total_agent_commission_amount' => $this->resource['total_agent_commission_amount'],
            'total_agent_net_due' => $this->resource['total_agent_net_due'],
            'total_system_commission_amount' => $this->resource['total_system_commission_amount'],
            'total_company_net_due' => $this->resource['total_company_net_due'],
            'agent_to_system_amount' => $this->resource['agent_to_system_amount'],
            'system_to_agent_amount' => $this->resource['system_to_agent_amount'],
            'system_to_company_amount' => $this->resource['system_to_company_amount'],
            'company_to_system_amount' => $this->resource['company_to_system_amount'],
            'pending_cash_count' => $this->resource['pending_cash_count'],
            'system_net_profit' => $this->resource['system_net_profit'],
        ];
    }
}
