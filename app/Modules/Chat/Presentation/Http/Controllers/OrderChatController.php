<?php

namespace App\Modules\Chat\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chat\Application\UseCases\GetOrCreateOrderConversationUseCase;
use App\Modules\Chat\Infrastructure\Database\Models\Conversation;
use App\Modules\Chat\Presentation\Http\Resources\ConversationResource;
use App\Modules\Core\Infrastructure\Helpers\ApiResponse;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Http\JsonResponse;

class OrderChatController extends Controller
{
    public function __construct(
        private GetOrCreateOrderConversationUseCase $getOrCreateConversation,
    ) {}

    public function show(string $orderId): JsonResponse
    {
        $user = $this->resolveUser();
        $accountType = $user->resolveAccountType()
            ?? throw new \RuntimeException('Unknown account type');

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

    private function resolveUser(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
