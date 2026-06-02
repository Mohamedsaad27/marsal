<?php

namespace App\Modules\Locations\Application\Services;

use App\Modules\Locations\Domain\Interfaces\CityRepositoryInterface;
use App\Modules\Locations\Domain\Interfaces\GovernorateRepositoryInterface;
use Illuminate\Support\Str;

class LocationCodeGenerator
{
    public function __construct(
        private readonly GovernorateRepositoryInterface $governorateRepository,
        private readonly CityRepositoryInterface $cityRepository,
    ) {}

    public function forGovernorate(string $nameEn, ?string $preferred = null): string
    {
        return $this->uniqueGovernorateCode($preferred ?? Str::slug($nameEn));
    }

    public function forCity(string $governorateCode, string $nameEn, ?string $preferred = null): string
    {
        $base = $preferred ?? $governorateCode.'-'.Str::slug($nameEn);

        return $this->uniqueCityCode($base);
    }

    private function uniqueGovernorateCode(string $base): string
    {
        $code = $base !== '' ? $base : 'governorate';
        $suffix = 1;

        while ($this->governorateRepository->codeExists($code)) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }

    private function uniqueCityCode(string $base): string
    {
        $code = $base !== '' ? $base : 'city';
        $suffix = 1;

        while ($this->cityRepository->codeExists($code)) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }
}
