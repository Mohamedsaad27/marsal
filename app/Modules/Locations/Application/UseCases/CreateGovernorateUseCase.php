<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\Locations\Application\DTOs\CreateGovernorateDTO;
use App\Modules\Locations\Application\Services\LocationCodeGenerator;
use App\Modules\Locations\Domain\Interfaces\GovernorateRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\Governorate;

class CreateGovernorateUseCase
{
    public function __construct(
        private readonly GovernorateRepositoryInterface $governorateRepository,
        private readonly LocationCodeGenerator $codeGenerator,
    ) {}

    public function execute(CreateGovernorateDTO $dto): Governorate
    {
        $code = $dto->code
            ? $this->codeGenerator->forGovernorate($dto->name_en, $dto->code)
            : $this->codeGenerator->forGovernorate($dto->name_en);

        return $this->governorateRepository->create([
            'name_ar' => $dto->name_ar,
            'name_en' => $dto->name_en,
            'code' => $code,
            'is_active' => $dto->is_active,
        ])->loadCount('cities');
    }
}
