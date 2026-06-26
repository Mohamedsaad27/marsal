<?php

namespace App\Modules\Collections\Application\UseCases\Admin;

use App\Modules\Collections\Application\DTOs\AdminCollectionFilterDTO;
use App\Modules\Collections\Domain\Interfaces\AdminCollectionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAdminCollectionsUseCase
{
    public function __construct(
        private AdminCollectionRepositoryInterface $repository,
    ) {}

    public function execute(AdminCollectionFilterDTO $filter): LengthAwarePaginator
    {
        return $this->repository->paginate($filter);
    }
}
