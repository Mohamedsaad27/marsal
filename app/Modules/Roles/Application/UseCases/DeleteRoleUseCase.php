<?php

namespace App\Modules\Roles\Application\UseCases;

use App\Modules\Roles\Domain\Interfaces\RoleRepositoryInterface;

class DeleteRoleUseCase
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    public function execute(int $id): void
    {
        $role = $this->roleRepository->findById($id);

        if ($role === null) {
            throw new \RuntimeException('Role not found.');
        }

        $this->roleRepository->delete($role);
    }
}
