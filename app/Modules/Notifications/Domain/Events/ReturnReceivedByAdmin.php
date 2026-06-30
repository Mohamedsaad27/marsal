<?php

namespace App\Modules\Notifications\Domain\Events;

readonly class ReturnReceivedByAdmin
{
    public function __construct(
        public string $returnId,
        public string $orderId,
        public string $orderCode,
        public string $agentName,
    ) {}
}
