<?php

namespace App\Modules\Settings\Application\UseCases;

use App\Modules\Core\Infrastructure\Services\MediaStorageService;
use App\Modules\Settings\Application\DTOs\UpdateSettingsDTO;
use App\Modules\Settings\Domain\Enums\SettingKeyEnum;
use App\Modules\Settings\Domain\Interfaces\SettingRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Throwable;

class UpdateSettingsUseCase
{
    public function __construct(
        private readonly SettingRepositoryInterface $repo,
        private readonly MediaStorageService $mediaStorage,
    ) {}

    public function execute(UpdateSettingsDTO $dto): void
    {
        $settings = $dto->settings;

        if (isset($settings[SettingKeyEnum::LogoUrl->value]) && $settings[SettingKeyEnum::LogoUrl->value] instanceof UploadedFile) {
            $path = $this->storeLogo($settings[SettingKeyEnum::LogoUrl->value]);
            if ($path !== null) {
                $settings[SettingKeyEnum::LogoUrl->value] = $path;
            } else {
                unset($settings[SettingKeyEnum::LogoUrl->value]);
            }
        }

        $this->repo->setMany($settings);
    }

    private function storeLogo(UploadedFile $file): ?string
    {
        try {
            $stored = $this->mediaStorage->store(
                $file,
                'system',
                'settings',
                'logo'
            );

            $oldLogo = $this->repo->get(SettingKeyEnum::LogoUrl);
            $this->deleteOldLogo($oldLogo, $stored['disk']);

            return $stored['file_path'];
        } catch (Throwable) {
            return null;
        }
    }

    private function deleteOldLogo(?string $oldLogo, string $disk): void
    {
        if ($oldLogo === null || $oldLogo === '') {
            return;
        }

        if (str_starts_with($oldLogo, 'http://') || str_starts_with($oldLogo, 'https://')) {
            return;
        }

        try {
            $this->mediaStorage->delete($disk, $oldLogo);
        } catch (Throwable) {
            // Ignore error
        }
    }
}
