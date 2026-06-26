<?php

namespace App\Modules\Orders\Application\UseCases\Company;

use App\Modules\Orders\Application\DTOs\CompanyOrderFilterDTO;
use App\Modules\Orders\Domain\Interfaces\CompanyOrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListCompanyOrdersUseCase
{
    public function __construct(
        private CompanyOrderRepositoryInterface $repository,
    ) {}

    public function execute(CompanyOrderFilterDTO $filter, string $companyId): LengthAwarePaginator
    {
        return $this->repository->paginate($filter, $companyId);
    }
}
