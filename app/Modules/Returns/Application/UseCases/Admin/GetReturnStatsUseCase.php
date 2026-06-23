<?php

namespace App\Modules\Returns\Application\UseCases\Admin;

use App\Modules\Returns\Domain\Interfaces\ReturnRepositoryInterface;

class GetReturnStatsUseCase
{
    public function __construct(
        private ReturnRepositoryInterface $repository,
    ) {}

    public function execute(): array
    {
        return $this->repository->stats();
    }
}
