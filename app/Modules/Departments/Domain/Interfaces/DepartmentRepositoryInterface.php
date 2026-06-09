<?php

namespace App\Modules\Departments\Domain\Interfaces;

use App\Modules\Departments\Application\DTOs\CreateDepartmentDTO;
use App\Modules\Departments\Application\DTOs\UpdateDepartmentDTO;
use App\Modules\Departments\Infrastructure\Database\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DepartmentRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function findOrFail(string $id): Department;

    public function findWithTrashed(string $id): ?Department;

    public function create(CreateDepartmentDTO $dto): Department;

    public function update(Department $department, UpdateDepartmentDTO $dto): Department;

    public function delete(Department $department): void;

    public function restore(string $id): Department;

    public function hasActiveMembers(Department $department): bool;

    public function managerExists(string $managerId): bool;

    /**
     * @return array{total_departments: int, total_active: int, total_members: int}
     */
    public function listKpis(): array;
}
