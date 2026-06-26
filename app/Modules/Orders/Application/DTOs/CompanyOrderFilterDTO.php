<?php

namespace App\Modules\Orders\Application\DTOs;

readonly class CompanyOrderFilterDTO
{
    public function __construct(
        public ?string $status = null,
        public ?string $search = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public int $perPage = 20,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            status:   $data['status'] ?? null,
            search:   $data['search'] ?? null,
            dateFrom: $data['date_from'] ?? null,
            dateTo:   $data['date_to'] ?? null,
            perPage:  isset($data['per_page']) ? min((int) $data['per_page'], 100) : 20,
        );
    }
}
