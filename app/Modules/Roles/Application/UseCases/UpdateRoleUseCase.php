<?php

namespace App\Modules\Roles\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Roles\Domain\Interfaces\RoleRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class UpdateRoleUseCase
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(int $id, string $name): Role
    {
        $role = $this->roleRepository->findById($id);

        if ($role === null) {
            throw new \RuntimeException('Role not found.');
        }

        $oldName = $role->name;
        $role = $this->roleRepository->update($role, $name);

        $this->recordAudit->execute(
            userId:        Auth::id(),
            event:         AuditEventEnum::Updated,
            auditableType: 'roles',
            auditableId:   (string) $role->id,
            oldValues:     ['name' => $oldName],
            newValues:     ['name' => $role->name],
        );

        return $role;
    }
}
