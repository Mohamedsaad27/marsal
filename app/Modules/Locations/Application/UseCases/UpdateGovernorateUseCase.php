<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\Locations\Application\DTOs\UpdateGovernorateDTO;
use App\Modules\Locations\Application\Services\LocationCodeGenerator;
use App\Modules\Locations\Domain\Interfaces\GovernorateRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\Governorate;

class UpdateGovernorateUseCase
{
    public function __construct(
        private readonly GovernorateRepositoryInterface $governorateRepository,
        private readonly GetGovernorateUseCase $getGovernorateUseCase,
        private readonly LocationCodeGenerator $codeGenerator,
    ) {}

    public function execute(string $governorateId, UpdateGovernorateDTO $dto): Governorate
    {
        $governorate = $this->getGovernorateUseCase->execute($governorateId);

        $code = $dto->code ?? $governorate->code;

        if ($code === null || $code === '') {
            $code = $this->codeGenerator->forGovernorate($dto->name_en);
        } elseif ($code !== $governorate->code) {
            $code = $this->codeGenerator->forGovernorate($dto->name_en, $code);
        }

        return $this->governorateRepository->update($governorate, [
            'name_ar' => $dto->name_ar,
            'name_en' => $dto->name_en,
            'code' => $code,
            'is_active' => $dto->is_active,
        ]);
    }
}
