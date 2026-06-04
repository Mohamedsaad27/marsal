<?php

namespace App\Modules\Users\Application\DTOs;

readonly class GetUsersDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $role = null,
        public ?int $isActive = null,
        public int $perPage = 15,
        public int $page = 1,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            role: $data['role'] ?? null,
            isActive: isset($data['is_active']) ? (int) $data['is_active'] : null,
            perPage: (int) ($data['per_page'] ?? 15),
            page: (int) ($data['page'] ?? 1),
        );
    }
}
