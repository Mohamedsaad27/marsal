<?php

namespace App\Modules\Settings\Application\UseCases;

use App\Modules\Core\Infrastructure\Services\MediaStorageService;
use App\Modules\Settings\Domain\Enums\SettingKeyEnum;
use App\Modules\Settings\Domain\Interfaces\SettingRepositoryInterface;

class GetSettingsUseCase
{
    public function __construct(
        private readonly SettingRepositoryInterface $repo,
        private readonly MediaStorageService $mediaStorage,
    ) {}

    /** Returns settings grouped by section */
    public function execute(): array
    {
        $all = $this->repo->all();

        if (isset($all[SettingKeyEnum::LogoUrl->value]) && $all[SettingKeyEnum::LogoUrl->value]) {
            $path = $all[SettingKeyEnum::LogoUrl->value];
            if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
                $all[SettingKeyEnum::LogoUrl->value] = $this->mediaStorage->url(config('core.media.default_disk', 'public'), $path);
            }
        }

        $pick = fn (array $keys) => array_intersect_key(
            $all,
            array_flip(array_map(fn ($k) => $k->value, $keys))
        );

        return [
            'identity'     => $pick(SettingKeyEnum::identityKeys()),
            'organization' => $pick(SettingKeyEnum::organizationKeys()),
        ];
    }
}
