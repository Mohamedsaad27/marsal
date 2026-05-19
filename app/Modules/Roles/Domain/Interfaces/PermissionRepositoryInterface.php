<?php

namespace App\Modules\Roles\Domain\Interfaces;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

interface PermissionRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?Permission;

    public function create(string $name, string $guard = 'api'): Permission;

    public function update(Permission $permission, string $name): Permission;

    public function delete(Permission $permission): void;
}
