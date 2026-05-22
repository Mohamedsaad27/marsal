<?php

namespace App\Modules\Users\Presentation\Http\Resources;

use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'welcome_whatsapp_url' => $this->when(
                ! empty($this->welcome_whatsapp_url),
                $this->welcome_whatsapp_url
            ),
            'whatsapp_support_phone' => (string) config('auth_module.platform_phone', '+201010865241'),
            'account_type' => $this->resolveAccountType()?->code(),
            'is_active' => $this->is_active,
            'roles' => $this->getRoleNames(),
            'shipping_company' => $this->whenLoaded('shippingCompany', fn () => [
                'shipping_company_id' => $this->shippingCompany?->shipping_company_id,
                'company_name' => $this->shippingCompany?->company_name,
            ]),
            'staff_member' => $this->whenLoaded('staffMember', fn () => [
                'staff_member_id' => $this->staffMember?->staff_member_id,
                'department' => $this->staffMember?->department,
                'job_title' => $this->staffMember?->job_title,
            ]),
            'delivery_agent' => $this->whenLoaded('deliveryAgent', fn () => [
                'delivery_agent_id' => $this->deliveryAgent?->delivery_agent_id,
                'supervisor_agent_id' => $this->deliveryAgent?->supervisor_agent_id,
                'is_supervisor' => $this->deliveryAgent?->isSupervisor(),
                'national_id' => $this->deliveryAgent?->national_id,
            ]),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
