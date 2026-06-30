<?php

namespace App\Modules\Notifications\Domain\Events;

/** Fired when an order is reassigned to a different delivery agent. */
readonly class OrderReassigned
{
    public function __construct(
        public string $orderId,
        public string $orderCode,
        public string $previousAgentName,
        public string $newAgentName,
    ) {}
}
