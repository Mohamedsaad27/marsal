<?php

namespace App\Modules\Users\Application\DTOs;

readonly class GetUsersDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $role = null,
        public ?int $isActive = null,
        public ?string $departmentId = null,
        public ?string $cityId = null,
        public ?int $commissionType = null,
        public int $perPage = 15,
        public int $page = 1,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            role: $data['role'] ?? null,
            isActive: isset($data['is_active']) ? (int) $data['is_active'] : null,
            departmentId: $data['department_id'] ?? null,
            cityId: $data['city_id'] ?? null,
            commissionType: isset($data['commission_type']) ? (int) $data['commission_type'] : null,
            perPage: (int) ($data['per_page'] ?? 15),
            page: (int) ($data['page'] ?? 1),
        );
    }

    public function withRole(string $role): self
    {
        return new self(
            search: $this->search,
            role: $role,
            isActive: $this->isActive,
            departmentId: $this->departmentId,
            cityId: $this->cityId,
            commissionType: $this->commissionType,
            perPage: $this->perPage,
            page: $this->page,
        );
    }
}
