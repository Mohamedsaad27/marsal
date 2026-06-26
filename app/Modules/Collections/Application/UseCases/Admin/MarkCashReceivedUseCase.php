<?php

namespace App\Modules\Collections\Application\UseCases\Admin;

use App\Modules\Collections\Domain\Interfaces\AdminCollectionRepositoryInterface;
use App\Modules\Collections\Infrastructure\Database\Models\Collection;

class MarkCashReceivedUseCase
{
    public function __construct(
        private AdminCollectionRepositoryInterface $repository,
    ) {}

    public function execute(string $collectionId, string $receivedBy): Collection
    {
        return $this->repository->markCashReceived($collectionId, $receivedBy);
    }
}
