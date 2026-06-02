<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\Locations\Application\Exceptions\LocationInUseException;
use App\Modules\Locations\Domain\Interfaces\GovernorateRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\City;

class DeleteGovernorateUseCase
{
    public function __construct(
        private readonly GovernorateRepositoryInterface $governorateRepository,
        private readonly GetGovernorateUseCase $getGovernorateUseCase,
    ) {}

    public function execute(string $governorateId): void
    {
        $governorate = $this->getGovernorateUseCase->execute($governorateId);

        if (City::query()->where('governorate_id', $governorateId)->exists()) {
            throw new LocationInUseException(__('locations::messages.governorate_has_cities'));
        }

        if ($this->governorateRepository->isGovernorateReferenced($governorateId)) {
            throw new LocationInUseException;
        }

        $this->governorateRepository->delete($governorate);
    }
}
