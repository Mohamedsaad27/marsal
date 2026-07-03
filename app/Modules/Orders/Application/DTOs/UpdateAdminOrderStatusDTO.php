<?php

namespace App\Modules\Orders\Application\DTOs;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;

readonly class UpdateAdminOrderStatusDTO
{
    public function __construct(
        public string $orderId,
        public string $adminUserId,
        public OrderStatusEnum $requestedStatus,
        public ?float $collectedAmount = null,
        public ?CollectionTypeEnum $collectionType = null,
        public ?float $newCodAmount = null,
        public ?string $postponedDate = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(string $orderId, string $adminUserId, array $data): self
    {
        return new self(
            orderId: $orderId,
            adminUserId: $adminUserId,
            requestedStatus: OrderStatusEnum::from((int) $data['status_id']),
            collectedAmount: isset($data['collected_amount']) ? (float) $data['collected_amount'] : null,
            collectionType: isset($data['collection_type'])
                ? CollectionTypeEnum::from((int) $data['collection_type'])
                : null,
            newCodAmount: isset($data['new_cod_amount']) ? (float) $data['new_cod_amount'] : null,
            postponedDate: $data['postponed_date'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
