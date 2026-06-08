<?php

namespace App\Modules\AuditLog\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'event'          => [
                'code'  => $this->event->value,
                'label' => $this->event->labelAr(),
            ],
            'actor_type'     => $this->actor_type->value,
            'actor_type_label' => $this->actor_type->labelAr(),
            'actor'          => $this->when(
                $this->relationLoaded('actor') && $this->actor,
                fn () => [
                    'id'    => $this->actor->user_id,
                    'name'  => $this->actor->name,
                    'phone' => $this->actor->phone,
                ],
            ),
            'auditable_type' => $this->auditable_type,
            'auditable_id'   => $this->auditable_id,
            'description'    => $this->description,
            'old_values'     => $this->old_values,
            'new_values'     => $this->new_values,
            'metadata'       => $this->metadata,
            'ip_address'     => $this->ip_address,
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
