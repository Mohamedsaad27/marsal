<?php

namespace App\Modules\Roles\Application\UseCases;

use App\Modules\Roles\Domain\Interfaces\RoleRepositoryInterface;
use Spatie\Permission\Models\Role;

class CreateRoleUseCase
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    public function execute(string $name): Role
    {
        return $this->roleRepository->create($name);
    }
}
