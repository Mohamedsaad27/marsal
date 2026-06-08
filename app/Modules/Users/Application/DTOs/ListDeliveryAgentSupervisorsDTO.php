<?php

namespace App\Modules\Users\Application\DTOs;

readonly class ListDeliveryAgentSupervisorsDTO
{
    public function __construct(
        public ?string $search = null,
        public ?int $isActive = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            isActive: isset($data['is_active']) ? (int) $data['is_active'] : null,
        );
    }
}
