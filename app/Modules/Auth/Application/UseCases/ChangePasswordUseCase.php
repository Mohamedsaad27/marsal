<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Auth\Application\Exceptions\InvalidCurrentPasswordException;
use App\Modules\Auth\Application\Exceptions\PasswordReuseException;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Support\Facades\Hash;

class ChangePasswordUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(User $user, string $currentPassword, string $newPassword): void
    {
        if (! $user->is_active) {
            throw new InvalidCurrentPasswordException();
        }

        if (! Hash::check($currentPassword, $user->password)) {
            throw new InvalidCurrentPasswordException();
        }

        if (Hash::check($newPassword, $user->password)) {
            throw new PasswordReuseException();
        }

        if (strcasecmp($newPassword, $user->email) === 0) {
            throw new PasswordReuseException(__('auth::messages.password_same_as_email'));
        }

        $this->userRepository->updatePassword($user, $newPassword);

        $this->recordAudit->execute(
            userId:        $user->user_id,
            event:         AuditEventEnum::PasswordChanged,
            auditableType: 'users',
            auditableId:   $user->user_id,
        );
    }
}
