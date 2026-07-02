<?php

namespace App\Modules\Chat\Application\DTOs;

readonly class ListConversationsDTO
{
    public function __construct(
        public int $perPage = 15,
        public ?string $search = null,
        public ?string $orderId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            perPage: min((int) ($data['per_page'] ?? 15), 100),
            search: $data['search'] ?? null,
            orderId: $data['order_id'] ?? null,
        );
    }
}
