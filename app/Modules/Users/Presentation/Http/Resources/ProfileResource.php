<?php

namespace App\Modules\Users\Presentation\Http\Resources;

use App\Modules\Core\Infrastructure\Services\MediaStorageService;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'avatar_url' => $this->resolveAvatarUrl($this->avatar),
            'department' => $this->whenLoaded('staffMember', function () {
                $staffMember = $this->staffMember;

                if ($staffMember === null || $staffMember->department_id === null) {
                    return null;
                }

                if ($staffMember->relationLoaded('department') && $staffMember->department !== null) {
                    return $staffMember->department->name_ar;
                }

                return null;
            }),
            'job_title' => $this->whenLoaded('staffMember', fn () => $this->staffMember?->job_title),
            'roles' => $this->getRoleNames()->values()->all(),
        ];
    }

    private function resolveAvatarUrl(?string $avatar): ?string
    {
        if ($avatar === null || $avatar === '') {
            return null;
        }

        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return $avatar;
        }

        $disk = config('core.media.default_disk', 'public');

        return app(MediaStorageService::class)->url($disk, $avatar);
    }
}
