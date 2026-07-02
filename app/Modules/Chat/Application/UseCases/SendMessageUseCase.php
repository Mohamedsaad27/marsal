<?php

namespace App\Modules\Chat\Application\UseCases;

use App\Modules\Chat\Application\DTOs\SendMessageDTO;
use App\Modules\Chat\Application\Exceptions\ConversationAccessDeniedException;
use App\Modules\Chat\Application\Exceptions\ConversationNotFoundException;
use App\Modules\Chat\Application\Exceptions\OrderChatNotAllowedException;
use App\Modules\Chat\Domain\Interfaces\ChatRepositoryInterface;
use App\Modules\Chat\Domain\Services\ChatAccessService;
use App\Modules\Chat\Infrastructure\Database\Models\Conversation;
use App\Modules\Chat\Infrastructure\Database\Models\Message;
use App\Modules\Notifications\Domain\Events\NewMessageSent;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class SendMessageUseCase
{
    public function __construct(
        private ChatRepositoryInterface $repository,
        private ChatAccessService $accessService,
        private GetConversationUseCase $getConversation,
    ) {}

    public function execute(SendMessageDTO $dto, AccountTypeEnum $accountType): array
    {
        if (! in_array($accountType, [
            AccountTypeEnum::ShippingCompany,
            AccountTypeEnum::DeliveryAgent,
        ], true)) {
            throw new ConversationAccessDeniedException;
        }

        $conversation = $this->getConversation->execute(
            $dto->conversationId,
            $dto->senderUserId,
            $accountType,
        );

        $order = Order::query()->find($conversation['order_id']);

        if (! $order || ! $this->accessService->userCanSendMessage($dto->senderUserId, $order, $accountType)) {
            throw new ConversationAccessDeniedException;
        }

        $participants = $this->accessService->resolveOrderParticipants($order);

        if (! $participants) {
            throw new OrderChatNotAllowedException;
        }

        $message = DB::transaction(function () use ($dto, $conversation) {
            $messageData = $this->repository->createMessage([
                'conversation_id' => $dto->conversationId,
                'sender_id' => $dto->senderUserId,
                'body' => $dto->body,
                'message_type' => $dto->messageType->value,
            ]);

            if ($dto->attachment) {
                $messageModel = Message::query()->find($messageData['message_id']);
                $messageModel->addMedia(
                    $dto->attachment,
                    $dto->messageType->mediaCollection(),
                );
            }

            Conversation::query()
                ->where('conversation_id', $dto->conversationId)
                ->update(['updated_at' => now()]);

            return $this->repository->findMessageById($messageData['message_id']);
        });

        $sender = User::query()->find($dto->senderUserId);
        $recipientUserId = $this->accessService->getRecipientUserId($dto->senderUserId, $participants);
        $orderCode = $order->reference_code ?? $order->reference_no ?? '';

        Event::dispatch(new NewMessageSent(
            recipientUserId: $recipientUserId,
            senderName: $sender?->name ?? '',
            orderCode: $orderCode,
            orderId: $order->order_id,
            conversationId: $dto->conversationId,
        ));

        return $message;
    }
}
