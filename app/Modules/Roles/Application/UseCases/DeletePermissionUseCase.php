<?php

namespace App\Modules\Roles\Application\UseCases;

use App\Modules\Roles\Domain\Interfaces\PermissionRepositoryInterface;

class DeletePermissionUseCase
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {}

    public function execute(int $id): void
    {
        $permission = $this->permissionRepository->findById($id);

        if ($permission === null) {
            throw new \RuntimeException('Permission not found.');
        }

        $this->permissionRepository->delete($permission);
    }
}
