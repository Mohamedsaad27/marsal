<?php

namespace App\Modules\Departments\Presentation\Http\Resources;

use App\Modules\Departments\Infrastructure\Database\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Department */
class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->department_id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'manager' => $this->when(
                $this->manager_id !== null,
                fn () => [
                    'id' => $this->manager?->user_id,
                    'name' => $this->manager?->name,
                    'email' => $this->manager?->email,
                    'phone' => $this->manager?->phone,
                ],
            ),
            'staff_count' => $this->when(
                isset($this->staff_members_count) || $this->relationLoaded('staffMembers'),
                $this->staff_members_count ?? $this->staffMembers->count(),
            ),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
