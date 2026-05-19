<?php

namespace App\Modules\Roles\Application\UseCases;

use App\Modules\Roles\Domain\Interfaces\RoleRepositoryInterface;
use Illuminate\Support\Collection;

class ListRolesUseCase
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    public function execute(): Collection
    {
        return $this->roleRepository->all();
    }
}
