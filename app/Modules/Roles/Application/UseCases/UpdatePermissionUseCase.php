<?php

namespace App\Modules\Roles\Application\UseCases;

use App\Modules\Roles\Domain\Interfaces\PermissionRepositoryInterface;
use Spatie\Permission\Models\Permission;

class UpdatePermissionUseCase
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {}

    public function execute(int $id, string $name): Permission
    {
        $permission = $this->permissionRepository->findById($id);

        if ($permission === null) {
            throw new \RuntimeException('Permission not found.');
        }

        return $this->permissionRepository->update($permission, $name);
    }
}
