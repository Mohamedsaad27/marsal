<?php

namespace App\Modules\Roles\Application\UseCases;

use App\Modules\Roles\Domain\Interfaces\PermissionRepositoryInterface;
use Illuminate\Support\Collection;

class ListPermissionsUseCase
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {}

    public function execute(): Collection
    {
        return $this->permissionRepository->all();
    }
}
