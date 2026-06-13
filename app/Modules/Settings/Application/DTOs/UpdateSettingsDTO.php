<?php

namespace App\Modules\Settings\Application\DTOs;

readonly class UpdateSettingsDTO
{
    /** @param array<string, mixed> $settings  keyed by SettingKeyEnum value */
    public function __construct(
        public array $settings,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(settings: $data);
    }
}
