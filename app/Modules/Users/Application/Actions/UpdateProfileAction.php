<?php

namespace App\Modules\Users\Application\Actions;

use App\Modules\Core\Infrastructure\Services\MediaStorageService;
use App\Modules\Users\Application\DTOs\UpdateProfileData;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Throwable;

class UpdateProfileAction
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly MediaStorageService $mediaStorage,
    ) {}

    public function execute(User $user, UpdateProfileData $data): User
    {
        $attributes = $data->userAttributes();

        if ($data->hasAvatar()) {
            $avatarPath = $this->storeAvatar($user, $data);

            if ($avatarPath !== null) {
                $attributes['avatar'] = $avatarPath;
            }
        }

        if ($attributes !== []) {
            $this->repository->update($user, $attributes);
            $user->refresh();
        }

        // TODO: fire ProfileUpdatedEvent

        return $user->fresh()->load(['roles', 'staffMember']);
    }

    private function storeAvatar(User $user, UpdateProfileData $data): ?string
    {
        try {
            $stored = $this->mediaStorage->store(
                $data->avatar,
                'user',
                $user->user_id,
                'avatar',
            );

            $this->deleteOldAvatar($user->avatar, $stored['disk']);

            return $stored['file_path'];
        } catch (Throwable) {
            return null;
        }
    }

    private function deleteOldAvatar(?string $oldAvatar, string $disk): void
    {
        if ($oldAvatar === null || $oldAvatar === '') {
            return;
        }

        if (str_starts_with($oldAvatar, 'http://') || str_starts_with($oldAvatar, 'https://')) {
            return;
        }

        try {
            $this->mediaStorage->delete($disk, $oldAvatar);
        } catch (Throwable) {
            // Old avatar cleanup failure must not block the profile update.
        }
    }
}
