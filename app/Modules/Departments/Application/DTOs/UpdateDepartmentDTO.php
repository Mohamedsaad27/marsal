<?php

namespace App\Modules\Departments\Application\DTOs;

readonly class UpdateDepartmentDTO
{
    /**
     * @param  list<string>  $presentKeys
     */
    public function __construct(
        public array $presentKeys,
        public ?string $name_ar = null,
        public ?string $name_en = null,
        public ?string $description = null,
        public ?string $manager_id = null,
        public ?bool $is_active = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            presentKeys: array_keys($data),
            name_ar: $data['name_ar'] ?? null,
            name_en: $data['name_en'] ?? null,
            description: array_key_exists('description', $data) ? $data['description'] : null,
            manager_id: array_key_exists('manager_id', $data) ? $data['manager_id'] : null,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        );
    }

    public function has(string $key): bool
    {
        return in_array($key, $this->presentKeys, true);
    }
}
