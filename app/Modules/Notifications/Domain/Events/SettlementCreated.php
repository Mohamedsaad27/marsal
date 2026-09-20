<?php

namespace App\Modules\Notifications\Domain\Events;

use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;

readonly class SettlementCreated
{
    public function __construct(
        public string $settlementId,
        public string $entityLabel,
        public string $paymentDirection,
        public string $payableAmount,
        public SettlementTypeEnum $settlementType,
        public ?string $agentUserId = null,
        public ?string $companyUserId = null,
    ) {}
}
