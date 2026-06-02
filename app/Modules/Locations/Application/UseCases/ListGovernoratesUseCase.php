<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\Locations\Domain\Interfaces\GovernorateRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListGovernoratesUseCase
{
    public function __construct(
        private readonly GovernorateRepositoryInterface $governorateRepository,
    ) {}

    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->governorateRepository->paginate($filters, $perPage);
    }
}
