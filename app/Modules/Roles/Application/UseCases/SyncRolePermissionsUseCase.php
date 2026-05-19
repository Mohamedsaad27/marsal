<?php

namespace App\Modules\Roles\Application\UseCases;

use App\Modules\Roles\Domain\Interfaces\RoleRepositoryInterface;
use Spatie\Permission\Models\Role;

class SyncRolePermissionsUseCase
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
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

        $this->roleRepository->syncPermissions($role, $permissionNames);

        return $role->load('permissions');
    }
}
