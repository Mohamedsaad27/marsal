<?php

namespace App\Modules\Roles\Application\UseCases;

use App\Modules\Roles\Domain\Interfaces\RoleRepositoryInterface;
use Spatie\Permission\Models\Role;

class UpdateRoleUseCase
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    public function execute(int $id, string $name): Role
    {
        $role = $this->roleRepository->findById($id);

        if ($role === null) {
            throw new \RuntimeException('Role not found.');
        }

        return $this->roleRepository->update($role, $name);
    }
}
