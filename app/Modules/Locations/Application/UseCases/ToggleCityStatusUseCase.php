<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\Locations\Domain\Interfaces\CityRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\City;

class ToggleCityStatusUseCase
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
        private readonly GetCityUseCase $getCityUseCase,
    ) {}

    public function execute(string $cityId): City
    {
        $city = $this->getCityUseCase->execute($cityId);

        return $this->cityRepository->toggleStatus($city);
    }
}
