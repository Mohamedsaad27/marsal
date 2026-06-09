<?php

namespace App\Modules\Departments\Infrastructure\Persistence;

use App\Modules\Departments\Application\DTOs\CreateDepartmentDTO;
use App\Modules\Departments\Application\DTOs\UpdateDepartmentDTO;
use App\Modules\Departments\Application\Exceptions\DepartmentNotFoundException;
use App\Modules\Departments\Domain\Interfaces\DepartmentRepositoryInterface;
use App\Modules\Departments\Infrastructure\Database\Models\Department;
use App\Modules\Users\Infrastructure\Database\Models\StaffMember;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Department::query()
            ->with('manager')
            ->withCount('staffMembers')
            ->orderBy('name_ar');

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function findOrFail(string $id): Department
    {
        $department = Department::query()
            ->with(['manager', 'staffMembers'])
            ->withCount('staffMembers')
            ->find($id);

        if ($department === null) {
            throw new DepartmentNotFoundException;
        }

        return $department;
    }

    public function findWithTrashed(string $id): ?Department
    {
        return Department::query()
            ->withTrashed()
            ->with(['manager', 'staffMembers'])
            ->withCount('staffMembers')
            ->find($id);
    }

    public function create(CreateDepartmentDTO $dto): Department
    {
        return Department::query()->create([
            'name_ar' => $dto->name_ar,
            'name_en' => $dto->name_en,
            'description' => $dto->description,
            'manager_id' => $dto->manager_id,
            'is_active' => $dto->is_active,
        ])->load('manager');
    }

    public function update(Department $department, UpdateDepartmentDTO $dto): Department
    {
        $data = [];

        if ($dto->has('name_ar')) {
            $data['name_ar'] = $dto->name_ar;
        }

        if ($dto->has('name_en')) {
            $data['name_en'] = $dto->name_en;
        }

        if ($dto->has('description')) {
            $data['description'] = $dto->description;
        }

        if ($dto->has('manager_id')) {
            $data['manager_id'] = $dto->manager_id;
        }

        if ($dto->has('is_active')) {
            $data['is_active'] = $dto->is_active;
        }

        if ($data !== []) {
            $department->update($data);
        }

        return $department->fresh()->load(['manager', 'staffMembers'])->loadCount('staffMembers');
    }

    public function delete(Department $department): void
    {
        $department->delete();
    }

    public function restore(string $id): Department
    {
        $department = $this->findWithTrashed($id);

        if ($department === null) {
            throw new DepartmentNotFoundException;
        }

        $department->restore();

        return $department->fresh()->load(['manager', 'staffMembers'])->loadCount('staffMembers');
    }

    public function hasActiveMembers(Department $department): bool
    {
        return $department->staffMembers()
            ->whereNull('deleted_at')
            ->exists();
    }

    public function managerExists(string $managerId): bool
    {
        return User::query()
            ->where('user_id', $managerId)
            ->exists();
    }

    public function listKpis(): array
    {
        return [
            'total_departments' => Department::query()->count(),
            'total_active' => Department::query()->where('is_active', true)->count(),
            'total_members' => StaffMember::query()->whereNotNull('department_id')->count(),
        ];
    }
}
