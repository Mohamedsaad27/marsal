<?php

namespace App\Modules\Collections\Application\DTOs;

use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;

readonly class CreateSettlementDTO
{
    public function __construct(
        public SettlementTypeEnum $settlementType,
        public string $referenceEntityId,
        public string $periodFrom,
        public string $periodTo,
        public string $initiatedBy,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data, string $initiatedBy): self
    {
        return new self(
            settlementType: SettlementTypeEnum::from((int) $data['settlement_type']),
            referenceEntityId: $data['reference_entity_id'],
            periodFrom: $data['period_from'],
            periodTo: $data['period_to'],
            initiatedBy: $initiatedBy,
            notes: $data['notes'] ?? null,
        );
    }
}
