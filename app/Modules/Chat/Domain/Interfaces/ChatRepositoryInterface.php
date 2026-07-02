<?php

namespace App\Modules\Chat\Domain\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ChatRepositoryInterface
{
    public function findConversationById(string $conversationId): ?array;

    public function findConversationByOrderId(string $orderId): ?array;

    public function createConversation(array $data): array;

    public function addParticipant(string $conversationId, string $userId): void;

    /** @return array<int, array<string, mixed>> */
    public function getParticipants(string $conversationId): array;

    public function isParticipant(string $conversationId, string $userId): bool;

    public function listForUser(string $userId, int $perPage, array $filters = []): LengthAwarePaginator;

    public function listAll(int $perPage, array $filters = []): LengthAwarePaginator;

    public function updateParticipantLastRead(string $conversationId, string $userId): void;

    public function createMessage(array $data): array;

    public function findMessageById(string $messageId): ?array;

    public function listMessages(string $conversationId, int $perPage): LengthAwarePaginator;

    public function getUnreadCountForUser(string $conversationId, string $userId): int;
}
