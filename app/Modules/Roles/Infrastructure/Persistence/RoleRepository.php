<?php

namespace App\Modules\Roles\Infrastructure\Persistence;

use App\Modules\Roles\Domain\Interfaces\RoleRepositoryInterface;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class RoleRepository implements RoleRepositoryInterface
{
    public function all(): Collection
    {
        return Role::query()->with('permissions')->where('guard_name', 'api')->orderBy('name')->get();
    }

    public function findById(int $id): ?Role
    {
        return Role::query()->with('permissions')->where('guard_name', 'api')->find($id);
    }

    public function create(string $name, string $guard = 'api'): Role
    {
        return Role::query()->create(['name' => $name, 'guard_name' => $guard])->load('permissions');
    }

    public function update(Role $role, string $name): Role
    {
        $role->update(['name' => $name]);

        return $role->fresh()->load('permissions');
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    public function syncPermissions(Role $role, array $permissionNames): void
    {
        $role->syncPermissions($permissionNames);
    }
}
