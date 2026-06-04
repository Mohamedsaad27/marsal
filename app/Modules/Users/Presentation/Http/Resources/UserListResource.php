<?php

namespace App\Modules\Users\Presentation\Http\Resources;

use App\Modules\Locations\Presentation\Http\Resources\AddressResource;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'avatar' => $this->avatar,
            'is_active' => (bool) $this->is_active,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'role' => $this->whenLoaded('roles', function () {
                $role = $this->roles->first();

                return $role ? [
                    'name' => $role->name,
                    'label' => __('users::roles.'.$role->name),
                ] : null;
            }),
            'shipping_company' => ShippingCompanyResource::make($this->whenLoaded('shippingCompany')),
            'delivery_agent' => DeliveryAgentResource::make($this->whenLoaded('deliveryAgent')),
            'staff_member' => StaffMemberResource::make($this->whenLoaded('staffMember')),
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),
        ];
    }
}
