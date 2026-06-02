<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\Locations\Domain\Interfaces\CityRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListCitiesUseCase
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
    ) {}

    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->cityRepository->paginate($filters, $perPage);
    }
}
