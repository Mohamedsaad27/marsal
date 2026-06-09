<?php

namespace App\Modules\Departments\Application\UseCases;

use App\Modules\Departments\Application\DTOs\CreateDepartmentDTO;
use App\Modules\Departments\Application\Exceptions\DepartmentManagerNotFoundException;
use App\Modules\Departments\Domain\Interfaces\DepartmentRepositoryInterface;
use App\Modules\Departments\Infrastructure\Database\Models\Department;

class CreateDepartmentUseCase
{
    public function __construct(
        private readonly DepartmentRepositoryInterface $repository,
    ) {}

    public function execute(CreateDepartmentDTO $dto): Department
    {
        if ($dto->manager_id !== null && ! $this->repository->managerExists($dto->manager_id)) {
            throw new DepartmentManagerNotFoundException;
        }

        return $this->repository->create($dto);
    }
}
