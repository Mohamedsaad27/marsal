<?php

namespace App\Modules\Roles\Application\UseCases;

use App\Modules\Roles\Domain\Interfaces\PermissionRepositoryInterface;
use Spatie\Permission\Models\Permission;

class CreatePermissionUseCase
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {}

    public function execute(string $name): Permission
    {
        return $this->permissionRepository->create($name);
    }
}
