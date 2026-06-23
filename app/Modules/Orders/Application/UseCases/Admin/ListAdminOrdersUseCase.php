<?php

namespace App\Modules\Orders\Application\UseCases\Admin;

use App\Modules\Orders\Application\DTOs\AdminOrderFilterDTO;
use App\Modules\Orders\Domain\Interfaces\AdminOrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminOrdersUseCase
{
    public function __construct(
        private AdminOrderRepositoryInterface $repository,
    ) {}

    public function execute(AdminOrderFilterDTO $filter): LengthAwarePaginator
    {
        return $this->repository->paginate($filter);
    }
}
