<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\Locations\Domain\Interfaces\GovernorateRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\Governorate;

class ToggleGovernorateStatusUseCase
{
    public function __construct(
        private readonly GovernorateRepositoryInterface $governorateRepository,
        private readonly GetGovernorateUseCase $getGovernorateUseCase,
    ) {}

    public function execute(string $governorateId): Governorate
    {
        $governorate = $this->getGovernorateUseCase->execute($governorateId);

        return $this->governorateRepository->toggleStatus($governorate);
    }
}
