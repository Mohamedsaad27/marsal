<?php

namespace App\Modules\Reports\Application\DTOs;

readonly class ReportFilterDTO
{
    public function __construct(
        public ?string $search = null,
        public ?int $status = null,
        public ?int $collectionType = null,
        public ?int $settlementType = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $agentId = null,
        public ?string $companyId = null,
        public ?string $governorateId = null,
        public ?int $isActive = null,
        public int $perPage = 20,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            status: isset($data['status']) ? (int) $data['status'] : null,
            collectionType: isset($data['collection_type']) ? (int) $data['collection_type'] : null,
            settlementType: isset($data['settlement_type']) ? (int) $data['settlement_type'] : null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            agentId: $data['agent_id'] ?? null,
            companyId: $data['company_id'] ?? null,
            governorateId: $data['governorate_id'] ?? null,
            isActive: isset($data['is_active']) ? (int) $data['is_active'] : null,
            perPage: min((int) ($data['per_page'] ?? 20), 100),
        );
    }
}
