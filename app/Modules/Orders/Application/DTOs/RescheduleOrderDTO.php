<?php

namespace App\Modules\Orders\Application\DTOs;

readonly class RescheduleOrderDTO
{
    public function __construct(
        public string $orderId,
        public string $deliveryAgentId,
        public string $userId,
        public string $postponedDate,
        public ?string $notes = null,
    ) {}

    public static function fromArray(
        string $orderId,
        string $deliveryAgentId,
        string $userId,
        array $data,
    ): self {
        return new self(
            orderId: $orderId,
            deliveryAgentId: $deliveryAgentId,
            userId: $userId,
            postponedDate: $data['postponed_date'],
            notes: $data['notes'] ?? null,
        );
    }
}
