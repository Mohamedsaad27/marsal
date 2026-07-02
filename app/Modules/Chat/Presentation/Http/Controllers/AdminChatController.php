<?php

namespace App\Modules\Chat\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chat\Application\DTOs\ListConversationsDTO;
use App\Modules\Chat\Application\UseCases\GetConversationUseCase;
use App\Modules\Chat\Application\UseCases\GetOrCreateOrderConversationUseCase;
use App\Modules\Chat\Application\UseCases\ListAdminConversationsUseCase;
use App\Modules\Chat\Application\UseCases\ListMessagesUseCase;
use App\Modules\Chat\Infrastructure\Database\Models\Conversation;
use App\Modules\Chat\Presentation\Http\Resources\ConversationResource;
use App\Modules\Chat\Presentation\Http\Resources\MessageResource;
use App\Modules\Core\Infrastructure\Helpers\ApiResponse;
use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminChatController extends Controller
{
    public function __construct(
        private ListAdminConversationsUseCase $listConversations,
        private GetConversationUseCase $getConversation,
        private ListMessagesUseCase $listMessages,
        private GetOrCreateOrderConversationUseCase $getOrCreateConversation,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $dto = ListConversationsDTO::fromArray($request->query());
        $conversations = $this->listConversations->execute($dto);

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
        $user = $this->resolveAdminUser();
        $accountType = $user->resolveAccountType()
            ?? AccountTypeEnum::StaffMember;

        $this->getConversation->execute($conversationId, $user->user_id, $accountType);

        return ApiResponse::success(
            new ConversationResource(Conversation::query()->with([
                'order.shippingCompany',
                'order.deliveryAgent.user',
                'participants.user',
            ])->find($conversationId)),
            __('chat::messages.show_success'),
        );
    }

    public function messages(Request $request, string $conversationId): JsonResponse
    {
        $user = $this->resolveAdminUser();
        $accountType = $user->resolveAccountType()
            ?? AccountTypeEnum::StaffMember;
        $perPage = min((int) $request->query('per_page', 30), 100);

        $messages = $this->listMessages->execute(
            $conversationId,
            $user->user_id,
            $accountType,
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

    public function getByOrder(string $orderId): JsonResponse
    {
        $user = $this->resolveAdminUser();
        $accountType = $user->resolveAccountType()
            ?? AccountTypeEnum::StaffMember;

        $conversation = $this->getOrCreateConversation->execute(
            $orderId,
            $user->user_id,
            $accountType,
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

    private function resolveAdminUser(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
