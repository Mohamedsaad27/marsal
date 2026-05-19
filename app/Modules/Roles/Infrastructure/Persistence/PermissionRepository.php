<?php

namespace App\Modules\Roles\Infrastructure\Persistence;

use App\Modules\Roles\Domain\Interfaces\PermissionRepositoryInterface;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function all(): Collection
    {
        return Permission::query()->where('guard_name', 'api')->orderBy('name')->get();
    }

    public function findById(int $id): ?Permission
    {
        return Permission::query()->where('guard_name', 'api')->find($id);
    }

    public function create(string $name, string $guard = 'api'): Permission
    {
        return Permission::query()->create(['name' => $name, 'guard_name' => $guard]);
    }

    public function update(Permission $permission, string $name): Permission
    {
        $permission->update(['name' => $name]);

        return $permission->fresh();
    }

    public function delete(Permission $permission): void
    {
        $permission->delete();
    }
}
