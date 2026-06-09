<?php

namespace App\Modules\Departments\Application\UseCases;

use App\Modules\Departments\Application\Exceptions\DepartmentHasMembersException;
use App\Modules\Departments\Domain\Interfaces\DepartmentRepositoryInterface;

class DeleteDepartmentUseCase
{
    public function __construct(
        private readonly DepartmentRepositoryInterface $repository,
    ) {}

    public function execute(string $departmentId): void
    {
        $department = $this->repository->findOrFail($departmentId);

        if ($this->repository->hasActiveMembers($department)) {
            throw new DepartmentHasMembersException;
        }

        $this->repository->delete($department);
    }
}
