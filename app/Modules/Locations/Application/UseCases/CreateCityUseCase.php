<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\Locations\Application\DTOs\CreateCityDTO;
use App\Modules\Locations\Application\Services\LocationCodeGenerator;
use App\Modules\Locations\Domain\Interfaces\CityRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\City;

class CreateCityUseCase
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
        private readonly GetGovernorateUseCase $getGovernorateUseCase,
        private readonly LocationCodeGenerator $codeGenerator,
    ) {}

    public function execute(CreateCityDTO $dto): City
    {
        $governorate = $this->getGovernorateUseCase->execute($dto->governorate_id);

        if (! $governorate->is_active) {
            throw new \InvalidArgumentException(__('locations::messages.governorate_inactive'));
        }

        $governorateCode = $governorate->code ?? 'gov';

        $code = $dto->code
            ? $this->codeGenerator->forCity($governorateCode, $dto->name_en, $dto->code)
            : $this->codeGenerator->forCity($governorateCode, $dto->name_en);

        return $this->cityRepository->create([
            'governorate_id' => $dto->governorate_id,
            'name_ar' => $dto->name_ar,
            'name_en' => $dto->name_en,
            'code' => $code,
            'is_active' => $dto->is_active,
        ]);
    }
}
