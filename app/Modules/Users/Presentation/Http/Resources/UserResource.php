<?php

namespace App\Modules\Users\Presentation\Http\Resources;

use App\Modules\Locations\Presentation\Http\Resources\AddressResource;
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
            'shipping_company' => ShippingCompanyResource::make($this->whenLoaded('shippingCompany')),
            'staff_member' => StaffMemberResource::make($this->whenLoaded('staffMember')),
            'delivery_agent' => DeliveryAgentResource::make($this->whenLoaded('deliveryAgent')),
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
