<?php

namespace App\Modules\Settings\Infrastructure\Persistence;

use App\Modules\Settings\Domain\Enums\SettingKeyEnum;
use App\Modules\Settings\Domain\Interfaces\SettingRepositoryInterface;
use App\Modules\Settings\Infrastructure\Database\Models\SystemSetting;

class SettingRepository implements SettingRepositoryInterface
{
    public function all(): array
    {
        return SystemSetting::query()
            ->pluck('value', 'key')
            ->all();
    }

    public function get(SettingKeyEnum $key): ?string
    {
        return SystemSetting::query()
            ->where('key', $key->value)
            ->value('value');
    }

    public function set(SettingKeyEnum $key, ?string $value): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key'   => $key->value],
            ['value' => $value],
        );
    }

    public function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set(SettingKeyEnum::from($key), $value);
        }
    }
}
