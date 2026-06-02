<?php

namespace App\Modules\Locations\Application\DTOs;

readonly class CreateCityDTO
{
    public function __construct(
        public string $governorate_id,
        public string $name_ar,
        public string $name_en,
        public ?string $code,
        public bool $is_active = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            governorate_id: $data['governorate_id'],
            name_ar: $data['name_ar'],
            name_en: $data['name_en'],
            code: $data['code'] ?? null,
            is_active: (bool) ($data['is_active'] ?? true),
        );
    }
}
