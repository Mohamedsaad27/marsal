<?php

namespace App\Modules\Orders\Application\DTOs;

readonly class AdminOrderFilterDTO
{
    public function __construct(
        public ?string $status = null,
        public ?string $companyId = null,
        public ?string $agentId = null,
        public ?string $governorateId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $search = null,
        public int $perPage = 20,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            status:       $data['status'] ?? null,
            companyId:    $data['company_id'] ?? null,
            agentId:      $data['agent_id'] ?? null,
            governorateId: $data['governorate_id'] ?? null,
            dateFrom:     $data['date_from'] ?? null,
            dateTo:       $data['date_to'] ?? null,
            search:       $data['search'] ?? null,
            perPage:      isset($data['per_page']) ? min((int) $data['per_page'], 100) : 20,
        );
    }
}
