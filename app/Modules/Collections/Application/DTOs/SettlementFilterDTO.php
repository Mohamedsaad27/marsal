<?php

namespace App\Modules\Collections\Application\DTOs;

readonly class SettlementFilterDTO
{
    public function __construct(
        public ?string $search = null,
        public ?int $settlementType = null,
        public ?int $status = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $companyId = null,
        public int $perPage = 20,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            settlementType: isset($data['settlement_type']) ? (int) $data['settlement_type'] : null,
            status: isset($data['status']) ? (int) $data['status'] : null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            companyId: $data['company_id'] ?? null,
            perPage: min((int) ($data['per_page'] ?? 20), 100),
        );
    }
}
