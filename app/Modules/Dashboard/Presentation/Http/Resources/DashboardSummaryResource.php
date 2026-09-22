<?php

namespace App\Modules\Dashboard\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_orders' => $this->resource['total_orders'],
            'total_orders_change_percent' => $this->resource['total_orders_change_percent'],
            'in_progress_orders' => $this->resource['in_progress_orders'],
            'in_delivery' => $this->resource['in_delivery'],
            'in_delivery_label' => $this->resource['in_delivery_label'],
            'delivered_this_week' => $this->resource['delivered_this_week'],
            'delivered_change_percent' => $this->resource['delivered_change_percent'],
            'total_cod_collected' => $this->resource['total_cod_collected'],
            'pending_cash_amount' => $this->resource['pending_cash_amount'],
            'pending_cash_count' => $this->resource['pending_cash_count'],
            'signed_agent_balance' => $this->resource['signed_agent_balance'],
            'agents_payable_to_system' => $this->resource['agents_payable_to_system'],
            'system_payable_to_agents' => $this->resource['system_payable_to_agents'],
            'signed_company_balance' => $this->resource['signed_company_balance'],
            'system_payable_to_companies' => $this->resource['system_payable_to_companies'],
            'companies_payable_to_system' => $this->resource['companies_payable_to_system'],
            'system_in_amount' => $this->resource['system_in_amount'],
            'system_out_amount' => $this->resource['system_out_amount'],
            'net_system_movement' => $this->resource['net_system_movement'],
            'system_payable_to_companies_change_percent' => $this->resource['system_payable_to_companies_change_percent'],
            'companies_payable_to_system_change_percent' => $this->resource['companies_payable_to_system_change_percent'],
        ];
    }
}
