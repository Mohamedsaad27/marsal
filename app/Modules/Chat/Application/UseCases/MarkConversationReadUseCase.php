<?php

namespace App\Modules\Chat\Application\UseCases;

use App\Modules\Chat\Domain\Interfaces\ChatRepositoryInterface;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;

class MarkConversationReadUseCase
{
    public function __construct(
        private ChatRepositoryInterface $repository,
        private GetConversationUseCase $getConversation,
    ) {}

    public function execute(string $conversationId, string $userId, AccountTypeEnum $accountType): void
    {
        $this->getConversation->execute($conversationId, $userId, $accountType);

        if (in_array($accountType, [
            AccountTypeEnum::SuperAdmin,
            AccountTypeEnum::StaffMember,
        ], true)) {
            return;
        }

        $this->repository->updateParticipantLastRead($conversationId, $userId);
    }
}
