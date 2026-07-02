<?php

namespace App\Modules\Chat\Infrastructure\Persistence\Repositories;

use App\Modules\Chat\Domain\Interfaces\ChatRepositoryInterface;
use App\Modules\Chat\Infrastructure\Database\Models\Conversation;
use App\Modules\Chat\Infrastructure\Database\Models\ConversationParticipant;
use App\Modules\Chat\Infrastructure\Database\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ChatRepository implements ChatRepositoryInterface
{
    public function findConversationById(string $conversationId): ?array
    {
        $conversation = Conversation::query()
            ->with([
                'order.shippingCompany.user',
                'order.deliveryAgent.user',
                'participants.user',
            ])
            ->find($conversationId);

        return $conversation?->toArray();
    }

    public function findConversationByOrderId(string $orderId): ?array
    {
        $conversation = Conversation::query()
            ->with([
                'order.shippingCompany.user',
                'order.deliveryAgent.user',
                'participants.user',
            ])
            ->where('order_id', $orderId)
            ->first();

        return $conversation?->toArray();
    }

    public function createConversation(array $data): array
    {
        return Conversation::create($data)->toArray();
    }

    public function addParticipant(string $conversationId, string $userId): void
    {
        ConversationParticipant::query()->firstOrCreate(
            [
                'conversation_id' => $conversationId,
                'user_id' => $userId,
            ],
        );
    }

    public function getParticipants(string $conversationId): array
    {
        return ConversationParticipant::query()
            ->with('user')
            ->where('conversation_id', $conversationId)
            ->get()
            ->toArray();
    }

    public function isParticipant(string $conversationId, string $userId): bool
    {
        return ConversationParticipant::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function listForUser(string $userId, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = Conversation::query()
            ->with([
                'order.shippingCompany',
                'order.deliveryAgent.user',
                'participants.user',
            ])
            ->withCount('messages')
            ->withMax('messages', 'created_at')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc('updated_at');

        $this->applyConversationFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function listAll(int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = Conversation::query()
            ->with([
                'order.shippingCompany',
                'order.deliveryAgent.user',
                'participants.user',
            ])
            ->withCount('messages')
            ->withMax('messages', 'created_at')
            ->orderByDesc('updated_at');

        $this->applyConversationFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function updateParticipantLastRead(string $conversationId, string $userId): void
    {
        ConversationParticipant::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }

    public function createMessage(array $data): array
    {
        return Message::create($data)->toArray();
    }

    public function findMessageById(string $messageId): ?array
    {
        $message = Message::query()
            ->with(['sender', 'mediaFiles'])
            ->find($messageId);

        return $message?->toArray();
    }

    public function listMessages(string $conversationId, int $perPage): LengthAwarePaginator
    {
        return Message::query()
            ->with(['sender', 'mediaFiles'])
            ->where('conversation_id', $conversationId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getUnreadCountForUser(string $conversationId, string $userId): int
    {
        $participant = ConversationParticipant::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->first();

        $query = Message::query()
            ->where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId);

        if ($participant?->last_read_at) {
            $query->where('created_at', '>', $participant->last_read_at);
        }

        return $query->count();
    }

    private function applyConversationFilters($query, array $filters): void
    {
        if (! empty($filters['order_id'])) {
            $query->where('order_id', $filters['order_id']);
        }

        if (! empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->whereHas('order', function ($q) use ($term) {
                $q->where('reference_code', 'like', $term)
                    ->orWhere('reference_no', 'like', $term);
            });
        }
    }
}
