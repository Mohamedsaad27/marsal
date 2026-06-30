<?php

namespace App\Modules\Notifications\Domain\Events;

readonly class SettlementPaid
{
    public function __construct(
        public string $settlementId,
        public string $entityLabel,
        public string $netAmount,
    ) {}
}
