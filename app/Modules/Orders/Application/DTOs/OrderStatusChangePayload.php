<?php

namespace App\Modules\Orders\Application\DTOs;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;

readonly class OrderStatusChangePayload
{
    public function __construct(
        public string $changedByUserId,
        public string $deliveryAgentId,
        public OrderStatusEnum $requestedStatus,
        public ?float $collectedAmount = null,
        public ?CollectionTypeEnum $collectionType = null,
        public ?float $newCodAmount = null,
        public ?string $postponedDate = null,
        public ?string $notes = null,
        public bool $notifySuperAdminsOnAgentStatusChange = true,
    ) {}
}
