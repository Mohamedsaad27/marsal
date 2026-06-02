<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\Locations\Application\Exceptions\CityNotFoundException;
use App\Modules\Locations\Domain\Interfaces\CityRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\City;

class GetCityUseCase
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
    ) {}

    public function execute(string $cityId): City
    {
        $city = $this->cityRepository->findById($cityId);

        if ($city === null) {
            throw new CityNotFoundException;
        }

        return $city;
    }
}
