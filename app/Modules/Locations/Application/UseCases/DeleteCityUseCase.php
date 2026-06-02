<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\Locations\Application\Exceptions\LocationInUseException;
use App\Modules\Locations\Domain\Interfaces\CityRepositoryInterface;

class DeleteCityUseCase
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
        private readonly GetCityUseCase $getCityUseCase,
    ) {}

    public function execute(string $cityId): void
    {
        $city = $this->getCityUseCase->execute($cityId);

        if ($this->cityRepository->isCityReferenced($cityId)) {
            throw new LocationInUseException;
        }

        $this->cityRepository->delete($city);
    }
}
