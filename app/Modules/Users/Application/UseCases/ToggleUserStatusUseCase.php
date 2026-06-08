<?php

namespace App\Modules\Users\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Users\Application\Exceptions\UserActionForbiddenException;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Support\Facades\Auth;

class ToggleUserStatusUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly GetUserUseCase $getUserUseCase,
        private readonly RecordAuditUseCase $recordAudit,
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

        $wasActive = $user->is_active;
        $user = $this->repository->toggleActive($user);

        $this->recordAudit->execute(
            userId:        $actorId,
            event:         $wasActive ? AuditEventEnum::Deactivated : AuditEventEnum::Activated,
            auditableType: 'users',
            auditableId:   $user->user_id,
            oldValues:     ['is_active' => $wasActive],
            newValues:     ['is_active' => $user->is_active],
        );

        return $user;
    }
}
