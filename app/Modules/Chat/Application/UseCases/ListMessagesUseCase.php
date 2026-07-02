<?php

namespace App\Modules\Chat\Application\UseCases;

use App\Modules\Chat\Domain\Interfaces\ChatRepositoryInterface;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListMessagesUseCase
{
    public function __construct(
        private ChatRepositoryInterface $repository,
        private GetConversationUseCase $getConversation,
    ) {}

    public function execute(
        string $conversationId,
        string $userId,
        AccountTypeEnum $accountType,
        int $perPage = 30,
    ): LengthAwarePaginator {
        $this->getConversation->execute($conversationId, $userId, $accountType);

        return $this->repository->listMessages($conversationId, min($perPage, 100));
    }
}
