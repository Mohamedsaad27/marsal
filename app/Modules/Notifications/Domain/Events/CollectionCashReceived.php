<?php

namespace App\Modules\Notifications\Domain\Events;

readonly class CollectionCashReceived
{
    public function __construct(
        public string $collectionId,
        public ?string $orderId,
        public string $orderCode,
        public string $agentName,
        public string $collectedAmount,
        public ?string $agentUserId,
        public ?string $companyUserId,
    ) {}
}
