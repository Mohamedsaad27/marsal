<?php

namespace App\Modules\Users\Presentation\Http\Resources;

use App\Modules\Departments\Presentation\Http\Resources\DepartmentResource;
use App\Modules\Users\Infrastructure\Database\Models\StaffMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/** @mixin StaffMember */
class StaffMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->staff_member_id,
            'department' => $this->departmentCode(),
            'department_details' => $this->when(
                $this->department_id !== null && $this->relationLoaded('department'),
                fn () => new DepartmentResource($this->department),
            ),
            'job_title' => $this->job_title,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function departmentCode(): ?string
    {
        if ($this->department_id === null || ! $this->relationLoaded('department')) {
            return null;
        }

        return $this->department?->name_en === null
            ? null
            : Str::of($this->department->name_en)->lower()->replace(' ', '_')->toString();
    }
}
