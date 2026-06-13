<?php

namespace App\Modules\Settings\Domain\Interfaces;

use App\Modules\Settings\Domain\Enums\SettingKeyEnum;

interface SettingRepositoryInterface
{
    /** Return all settings as  ['key' => 'value', ...] */
    public function all(): array;

    /** Return a single value or null */
    public function get(SettingKeyEnum $key): ?string;

    /** Upsert a single key */
    public function set(SettingKeyEnum $key, ?string $value): void;

    /** Upsert multiple keys at once — ['key' => 'value', ...] */
    public function setMany(array $data): void;
}
