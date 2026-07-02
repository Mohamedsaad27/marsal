<?php

namespace App\Modules\Locations\Application\UseCases;

class BulkDeleteCitiesUseCase
{
    public function __construct(
        private readonly DeleteCityUseCase $deleteCityUseCase,
    ) {}

    /** @param list<string> $cityIds */
    public function execute(array $cityIds): void
    {
        foreach (array_values(array_unique($cityIds)) as $cityId) {
            $this->deleteCityUseCase->execute($cityId);
        }
    }
}
