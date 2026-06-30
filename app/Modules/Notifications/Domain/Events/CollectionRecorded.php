<?php

namespace App\Modules\Notifications\Domain\Events;

/** Fired when an agent collects cash on delivery — notifies super admins. */
readonly class CollectionRecorded
{
    public function __construct(
        public string $orderId,
        public string $orderCode,
        public string $collectionId,
        public string $agentName,
        public string $collectedAmount,
    ) {}
}
