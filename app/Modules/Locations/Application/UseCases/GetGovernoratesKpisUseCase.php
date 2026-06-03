<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\Locations\Domain\Interfaces\GovernorateRepositoryInterface;

class GetGovernoratesKpisUseCase
{
    public function __construct(
        private readonly GovernorateRepositoryInterface $governorateRepository,
    ) {}

    /**
     * @return array{total_governorates:int,total_active:int,total_covered_cities:int}
     */
    public function execute(): array
    {
        return $this->governorateRepository->listKpis();
    }
}
