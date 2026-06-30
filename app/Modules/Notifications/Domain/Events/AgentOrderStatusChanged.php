<?php

namespace App\Modules\Notifications\Domain\Events;

/** Fired when an agent updates an order status — notifies super admins. */
readonly class AgentOrderStatusChanged
{
    public function __construct(
        public string $orderId,
        public string $orderCode,
        public string $agentName,
        public string $statusLabelAr,
    ) {}
}
