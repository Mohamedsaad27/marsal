<?php

namespace App\Modules\Collections\Application\UseCases\Admin;

use App\Modules\Collections\Application\DTOs\CreateSettlementDTO;
use App\Modules\Collections\Domain\Interfaces\SettlementRepositoryInterface;
use App\Modules\Collections\Infrastructure\Database\Models\Settlement;

class CreateSettlementUseCase
{
    public function __construct(
        private SettlementRepositoryInterface $repository,
    ) {}

    public function execute(CreateSettlementDTO $dto): Settlement
    {
        $collections = $this->repository->findEligibleCollections($dto);

        return $this->repository->createFromCollections($dto, $collections);
    }
}
