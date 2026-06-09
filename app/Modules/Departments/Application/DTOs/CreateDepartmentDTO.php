<?php

namespace App\Modules\Departments\Application\DTOs;

readonly class CreateDepartmentDTO
{
    public function __construct(
        public string $name_ar,
        public string $name_en,
        public ?string $description,
        public ?string $manager_id,
        public bool $is_active,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name_ar: $data['name_ar'],
            name_en: $data['name_en'],
            description: $data['description'] ?? null,
            manager_id: $data['manager_id'] ?? null,
            is_active: (bool) ($data['is_active'] ?? true),
        );
    }
}
