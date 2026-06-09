<?php

namespace App\Modules\Departments\Application\UseCases;

use App\Modules\Departments\Domain\Interfaces\DepartmentRepositoryInterface;

class GetDepartmentsKpisUseCase
{
    public function __construct(
        private readonly DepartmentRepositoryInterface $repository,
    ) {}

    /**
     * @return array{total_departments: int, total_active: int, total_members: int}
     */
    public function execute(): array
    {
        return $this->repository->listKpis();
    }
}
