<?php

namespace App\Modules\Departments\Application\UseCases;

use App\Modules\Departments\Application\Exceptions\DepartmentNotFoundException;
use App\Modules\Departments\Domain\Interfaces\DepartmentRepositoryInterface;
use App\Modules\Departments\Infrastructure\Database\Models\Department;

class RestoreDepartmentUseCase
{
    public function __construct(
        private readonly DepartmentRepositoryInterface $repository,
    ) {}

    public function execute(string $departmentId): Department
    {
        $department = $this->repository->findWithTrashed($departmentId);

        if ($department === null) {
            throw new DepartmentNotFoundException;
        }

        if (! $department->trashed()) {
            return $department->load(['manager', 'staffMembers'])->loadCount('staffMembers');
        }

        return $this->repository->restore($departmentId);
    }
}
