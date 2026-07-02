<?php

namespace App\Modules\Chat\Application\UseCases;

use App\Modules\Chat\Application\Exceptions\ConversationAccessDeniedException;
use App\Modules\Chat\Application\Exceptions\ConversationNotFoundException;
use App\Modules\Chat\Application\Exceptions\OrderChatNotAllowedException;
use App\Modules\Chat\Domain\Enums\ConversationTypeEnum;
use App\Modules\Chat\Domain\Interfaces\ChatRepositoryInterface;
use App\Modules\Chat\Domain\Services\ChatAccessService;
use App\Modules\Orders\Application\Exceptions\OrderNotFoundException;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use Illuminate\Support\Facades\DB;

class GetOrCreateOrderConversationUseCase
{
    public function __construct(
        private ChatRepositoryInterface $repository,
        private ChatAccessService $accessService,
    ) {}

    public function execute(string $orderId, string $userId, AccountTypeEnum $accountType): array
    {
        $order = Order::query()->find($orderId);

        if (! $order) {
            throw new OrderNotFoundException($orderId);
        }

        if (! $this->accessService->userCanAccessOrderChat($userId, $order, $accountType)) {
            throw new ConversationAccessDeniedException;
        }

        $participants = $this->accessService->resolveOrderParticipants($order);

        if (! $participants) {
            throw new OrderChatNotAllowedException;
        }

        $existing = $this->repository->findConversationByOrderId($orderId);

        if ($existing) {
            return $this->repository->findConversationById($existing['conversation_id']);
        }

        return DB::transaction(function () use ($orderId, $participants) {
            $conversation = $this->repository->createConversation([
                'order_id' => $orderId,
                'conversation_type' => ConversationTypeEnum::AgentCompany->value,
            ]);

            $this->repository->addParticipant($conversation['conversation_id'], $participants['company_user_id']);
            $this->repository->addParticipant($conversation['conversation_id'], $participants['agent_user_id']);

            return $this->repository->findConversationById($conversation['conversation_id']);
        });
    }
}
