<?php

namespace App\Modules\Chat\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chat\Application\DTOs\ListConversationsDTO;
use App\Modules\Chat\Application\UseCases\GetConversationUseCase;
use App\Modules\Chat\Application\UseCases\ListMessagesUseCase;
use App\Modules\Chat\Application\UseCases\ListUserConversationsUseCase;
use App\Modules\Chat\Application\UseCases\MarkConversationReadUseCase;
use App\Modules\Chat\Application\UseCases\SendMessageUseCase;
use App\Modules\Chat\Infrastructure\Database\Models\Conversation;
use App\Modules\Chat\Presentation\Http\Requests\SendMessageRequest;
use App\Modules\Chat\Presentation\Http\Resources\ConversationResource;
use App\Modules\Chat\Presentation\Http\Resources\MessageResource;
use App\Modules\Core\Infrastructure\Helpers\ApiResponse;
use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private ListUserConversationsUseCase $listConversations,
        private GetConversationUseCase $getConversation,
        private ListMessagesUseCase $listMessages,
        private SendMessageUseCase $sendMessage,
        private MarkConversationReadUseCase $markRead,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->resolveUser();
        $dto = ListConversationsDTO::fromArray($request->query());

        $conversations = $this->listConversations->execute($user->user_id, $dto);

        return ApiResponse::success(
            array_merge(
                ['items' => ConversationResource::collection($conversations->items())],
                PaginationMeta::getMeta($conversations),
            ),
            __('chat::messages.list_success'),
        );
    }

    public function show(string $conversationId): JsonResponse
    {
        $user = $this->resolveUser();
        $conversation = $this->getConversation->execute(
            $conversationId,
            $user->user_id,
            $user->resolveAccountType() ?? throw new \RuntimeException('Unknown account type'),
        );

        return ApiResponse::success(
            new ConversationResource(Conversation::query()->with([
                'order.shippingCompany',
                'order.deliveryAgent.user',
                'participants.user',
            ])->find($conversation['conversation_id'])),
            __('chat::messages.show_success'),
        );
    }

    public function messages(Request $request, string $conversationId): JsonResponse
    {
        $user = $this->resolveUser();
        $perPage = min((int) $request->query('per_page', 30), 100);

        $messages = $this->listMessages->execute(
            $conversationId,
            $user->user_id,
            $user->resolveAccountType() ?? throw new \RuntimeException('Unknown account type'),
            $perPage,
        );

        return ApiResponse::success(
            array_merge(
                ['items' => MessageResource::collection($messages->items())],
                PaginationMeta::getMeta($messages),
            ),
            __('chat::messages.messages_list_success'),
        );
    }

    public function send(SendMessageRequest $request, string $conversationId): JsonResponse
    {
        $user = $this->resolveUser();

        $message = $this->sendMessage->execute(
            $request->toDTO($conversationId, $user->user_id),
            $user->resolveAccountType() ?? throw new \RuntimeException('Unknown account type'),
        );

        return ApiResponse::success(
            new MessageResource(
                \App\Modules\Chat\Infrastructure\Database\Models\Message::query()
                    ->with(['sender', 'mediaFiles'])
                    ->find($message['message_id']),
            ),
            __('chat::messages.sent_success'),
            201,
        );
    }

    public function markRead(string $conversationId): JsonResponse
    {
        $user = $this->resolveUser();

        $this->markRead->execute(
            $conversationId,
            $user->user_id,
            $user->resolveAccountType() ?? throw new \RuntimeException('Unknown account type'),
        );

        return ApiResponse::success(null, __('chat::messages.marked_read'));
    }

    private function resolveUser(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
