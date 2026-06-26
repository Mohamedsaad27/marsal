<?php

namespace App\Modules\Collections\Application\UseCases\Admin;

use App\Modules\Collections\Domain\Interfaces\AdminCollectionRepositoryInterface;

class GetAdminCollectionStatsUseCase
{
    public function __construct(
        private AdminCollectionRepositoryInterface $repository,
    ) {}

    public function execute(): array
    {
        return $this->repository->stats();
    }
}
