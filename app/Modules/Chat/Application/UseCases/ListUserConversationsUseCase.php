<?php

namespace App\Modules\Chat\Application\UseCases;

use App\Modules\Chat\Application\DTOs\ListConversationsDTO;
use App\Modules\Chat\Domain\Interfaces\ChatRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListUserConversationsUseCase
{
    public function __construct(
        private ChatRepositoryInterface $repository,
    ) {}

    public function execute(string $userId, ListConversationsDTO $dto): LengthAwarePaginator
    {
        $filters = array_filter([
            'search' => $dto->search,
            'order_id' => $dto->orderId,
        ]);

        return $this->repository->listForUser($userId, $dto->perPage, $filters);
    }
}
