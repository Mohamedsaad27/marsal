<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\Locations\Application\Exceptions\GovernorateNotFoundException;
use App\Modules\Locations\Domain\Interfaces\GovernorateRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\Governorate;

class GetGovernorateUseCase
{
    public function __construct(
        private readonly GovernorateRepositoryInterface $governorateRepository,
    ) {}

    public function execute(string $governorateId): Governorate
    {
        $governorate = $this->governorateRepository->findById($governorateId);

        if ($governorate === null) {
            throw new GovernorateNotFoundException;
        }

        return $governorate;
    }
}
