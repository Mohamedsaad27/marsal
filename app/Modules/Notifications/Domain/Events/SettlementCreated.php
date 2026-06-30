<?php

namespace App\Modules\Notifications\Domain\Events;

readonly class SettlementCreated
{
    public function __construct(
        public string $settlementId,
        public string $entityLabel,
        public string $netAmount,
    ) {}
}
