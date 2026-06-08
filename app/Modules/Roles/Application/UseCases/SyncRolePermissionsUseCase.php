<?php

namespace App\Modules\Roles\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Roles\Domain\Interfaces\RoleRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class SyncRolePermissionsUseCase
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    /**
     * @param  array<int, string>  $permissionNames
     */
    public function execute(int $roleId, array $permissionNames): Role
    {
        $role = $this->roleRepository->findById($roleId);

        if ($role === null) {
            throw new \RuntimeException('Role not found.');
        }

        $oldPermissions = $role->permissions->pluck('name')->sort()->values()->all();

        $this->roleRepository->syncPermissions($role, $permissionNames);

        $role = $role->load('permissions');
        $newPermissions = $role->permissions->pluck('name')->sort()->values()->all();

        $this->recordAudit->execute(
            userId:        Auth::id(),
            event:         AuditEventEnum::Updated,
            auditableType: 'roles',
            auditableId:   (string) $role->id,
            oldValues:     ['permissions' => $oldPermissions],
            newValues:     ['permissions' => $newPermissions],
            metadata:      [
                'action'    => 'sync_permissions',
                'role_name' => $role->name,
            ],
        );

        return $role;
    }
}
