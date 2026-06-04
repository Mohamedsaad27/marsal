<?php

namespace App\Modules\Users\Presentation\Http\Resources;

use App\Modules\Users\Infrastructure\Database\Models\StaffMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StaffMember */
class StaffMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->staff_member_id,
            'department' => $this->department,
            'job_title' => $this->job_title,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
