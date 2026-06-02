<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\Locations\Application\DTOs\UpdateCityDTO;
use App\Modules\Locations\Application\Services\LocationCodeGenerator;
use App\Modules\Locations\Domain\Interfaces\CityRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\City;

class UpdateCityUseCase
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
        private readonly GetCityUseCase $getCityUseCase,
        private readonly GetGovernorateUseCase $getGovernorateUseCase,
        private readonly LocationCodeGenerator $codeGenerator,
    ) {}

    public function execute(string $cityId, UpdateCityDTO $dto): City
    {
        $city = $this->getCityUseCase->execute($cityId);
        $governorate = $this->getGovernorateUseCase->execute($dto->governorate_id);

        $governorateCode = $governorate->code ?? 'gov';
        $code = $dto->code;

        if ($code !== $city->code) {
            $code = $this->codeGenerator->forCity($governorateCode, $dto->name_en, $code);
        }

        return $this->cityRepository->update($city, [
            'governorate_id' => $dto->governorate_id,
            'name_ar' => $dto->name_ar,
            'name_en' => $dto->name_en,
            'code' => $code,
            'is_active' => $dto->is_active,
        ]);
    }
}
