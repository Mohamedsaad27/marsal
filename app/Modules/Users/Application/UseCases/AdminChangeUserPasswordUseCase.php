<?php

namespace App\Modules\Users\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Users\Application\DTOs\AdminChangeUserPasswordDTO;
use App\Modules\Users\Application\Exceptions\UserNotFoundException;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class AdminChangeUserPasswordUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(AdminChangeUserPasswordDTO $dto): void
    {
        $user = $this->repository->findById($dto->userId);

        if (! $user) {
            throw new UserNotFoundException();
        }

        $this->repository->updatePassword($user, $dto->password);

        $this->recordAudit->execute(
            userId:        Auth::id(),
            event:         AuditEventEnum::PasswordChanged,
            auditableType: 'users',
            auditableId:   $user->user_id,
            metadata:      ['changed_by_admin' => true],
        );
    }
}
