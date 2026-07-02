<?php

namespace App\Modules\Chat\Application\UseCases;

use App\Modules\Chat\Application\Exceptions\ConversationAccessDeniedException;
use App\Modules\Chat\Application\Exceptions\ConversationNotFoundException;
use App\Modules\Chat\Domain\Interfaces\ChatRepositoryInterface;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;

class GetConversationUseCase
{
    public function __construct(
        private ChatRepositoryInterface $repository,
    ) {}

    public function execute(string $conversationId, string $userId, AccountTypeEnum $accountType): array
    {
        $conversation = $this->repository->findConversationById($conversationId);

        if (! $conversation) {
            throw new ConversationNotFoundException($conversationId);
        }

        $isAdmin = in_array($accountType, [
            AccountTypeEnum::SuperAdmin,
            AccountTypeEnum::StaffMember,
        ], true);

        if (! $isAdmin && ! $this->repository->isParticipant($conversationId, $userId)) {
            throw new ConversationAccessDeniedException;
        }

        return $conversation;
    }
}
