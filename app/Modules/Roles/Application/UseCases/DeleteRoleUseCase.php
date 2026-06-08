<?php

namespace App\Modules\Roles\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Roles\Domain\Interfaces\RoleRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class DeleteRoleUseCase
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(int $id): void
    {
        $role = $this->roleRepository->findById($id);

        if ($role === null) {
            throw new \RuntimeException('Role not found.');
        }

        $this->recordAudit->execute(
            userId:        Auth::id(),
            event:         AuditEventEnum::Deleted,
            auditableType: 'roles',
            auditableId:   (string) $role->id,
            oldValues:     [
                'name'        => $role->name,
                'guard_name'  => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->values()->all(),
            ],
        );

        $this->roleRepository->delete($role);
    }
}
