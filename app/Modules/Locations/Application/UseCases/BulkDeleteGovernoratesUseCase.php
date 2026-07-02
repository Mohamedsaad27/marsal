<?php

namespace App\Modules\Locations\Application\UseCases;

class BulkDeleteGovernoratesUseCase
{
    public function __construct(
        private readonly DeleteGovernorateUseCase $deleteGovernorateUseCase,
    ) {}

    /** @param list<string> $governorateIds */
    public function execute(array $governorateIds): void
    {
        foreach (array_values(array_unique($governorateIds)) as $governorateId) {
            $this->deleteGovernorateUseCase->execute($governorateId);
        }
    }
}
