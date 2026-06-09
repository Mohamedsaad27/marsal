<?php

namespace App\Modules\Departments\Application\UseCases;

use App\Modules\Departments\Domain\Interfaces\DepartmentRepositoryInterface;
use App\Modules\Departments\Infrastructure\Database\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetDepartmentsUseCase
{
    public function __construct(
        private readonly DepartmentRepositoryInterface $repository,
    ) {}

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function show(string $departmentId): Department
    {
        return $this->repository->findOrFail($departmentId);
    }
}
