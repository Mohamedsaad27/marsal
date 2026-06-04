<?php

namespace App\Modules\Users\Application\UseCases;

use App\Modules\Users\Application\Exceptions\UserActionForbiddenException;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Support\Facades\Auth;

class ToggleUserStatusUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly GetUserUseCase $getUserUseCase,
    ) {}

    public function execute(string $userId): User
    {
        $user = $this->getUserUseCase->execute($userId);
        $actorId = Auth::id();

        if ($actorId === $user->user_id && $user->is_active) {
            throw new UserActionForbiddenException(__('users::messages.cannot_deactivate_self'));
        }

        if ($user->is_active && $user->hasRole('super_admin')) {
            if ($this->repository->countActiveUsersWithRole('super_admin') <= 1) {
                throw new UserActionForbiddenException(__('users::messages.cannot_deactivate_last_super_admin'));
            }
        }

        return $this->repository->toggleActive($user);
    }
}
