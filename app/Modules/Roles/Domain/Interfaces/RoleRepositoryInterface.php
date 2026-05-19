<?php

namespace App\Modules\Roles\Domain\Interfaces;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

interface RoleRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?Role;

    public function create(string $name, string $guard = 'api'): Role;

    public function update(Role $role, string $name): Role;

    public function delete(Role $role): void;

    public function syncPermissions(Role $role, array $permissionNames): void;
}
