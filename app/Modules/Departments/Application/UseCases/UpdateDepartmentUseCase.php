<?php

namespace App\Modules\Departments\Application\UseCases;

use App\Modules\Departments\Application\DTOs\UpdateDepartmentDTO;
use App\Modules\Departments\Application\Exceptions\DepartmentManagerNotFoundException;
use App\Modules\Departments\Application\Exceptions\DepartmentNotFoundException;
use App\Modules\Departments\Domain\Interfaces\DepartmentRepositoryInterface;
use App\Modules\Departments\Infrastructure\Database\Models\Department;

class UpdateDepartmentUseCase
{
    public function __construct(
        private readonly DepartmentRepositoryInterface $repository,
    ) {}

    public function execute(string $departmentId, UpdateDepartmentDTO $dto): Department
    {
        try {
            $department = $this->repository->findOrFail($departmentId);
        } catch (DepartmentNotFoundException) {
            throw new DepartmentNotFoundException;
        }

        if ($dto->has('manager_id') && $dto->manager_id !== null && ! $this->repository->managerExists($dto->manager_id)) {
            throw new DepartmentManagerNotFoundException;
        }

        return $this->repository->update($department, $dto);
    }
}
