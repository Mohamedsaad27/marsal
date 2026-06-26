<?php

namespace App\Modules\Collections\Domain\Interfaces;

use App\Modules\Collections\Application\DTOs\AdminCollectionFilterDTO;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminCollectionRepositoryInterface
{
    public function stats(): array;

    public function paginate(AdminCollectionFilterDTO $filter): LengthAwarePaginator;

    public function findOrFail(string $collectionId): Collection;

    public function markCashReceived(string $collectionId, string $receivedBy): Collection;
}
