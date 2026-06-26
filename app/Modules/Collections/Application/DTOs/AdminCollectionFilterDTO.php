<?php

namespace App\Modules\Collections\Application\DTOs;

readonly class AdminCollectionFilterDTO
{
    public function __construct(
        public ?string $search = null,
        public ?int $collectionType = null,
        public ?string $status = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $agentId = null,
        public ?string $companyId = null,
        public int $perPage = 20,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            collectionType: isset($data['collection_type']) ? (int) $data['collection_type'] : null,
            status: $data['status'] ?? null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            agentId: $data['agent_id'] ?? null,
            companyId: $data['company_id'] ?? null,
            perPage: min((int) ($data['per_page'] ?? 20), 100),
        );
    }
}
