<?php

namespace App\Modules\Locations\Application\DTOs;

readonly class CreateGovernorateDTO
{
    public function __construct(
        public string $name_ar,
        public string $name_en,
        public ?string $code,
        public bool $is_active = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name_ar: $data['name_ar'],
            name_en: $data['name_en'],
            code: $data['code'] ?? null,
            is_active: (bool) ($data['is_active'] ?? true),
        );
    }
}
