<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\Locations\Domain\Interfaces\CityRepositoryInterface;

class GetCitiesKpisUseCase
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
    ) {}

    /**
     * @return array{total_cities:int,total_active:int,total_covered_governorates:int}
     */
    public function execute(): array
    {
        return $this->cityRepository->listKpis();
    }
}
