<?php

namespace App\Modules\Orders\Application\UseCases\Admin;

use App\Modules\Orders\Domain\Interfaces\AdminOrderRepositoryInterface;

class GetAdminOrderStatsUseCase
{
    public function __construct(
        private AdminOrderRepositoryInterface $repository,
    ) {}

    public function execute(): array
    {
        return $this->repository->stats();
    }
}
