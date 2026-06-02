<?php

namespace App\Modules\Locations\Infrastructure\Database\Seeders;

use App\Modules\Locations\Infrastructure\Database\Models\City;
use App\Modules\Locations\Infrastructure\Database\Models\Governorate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Seeds Egypt governorates and cities from vendored Tech-Labs JSON (MIT).
 *
 * @see https://github.com/Tech-Labs/egypt-governorates-and-cities-db
 */
class EgyptLocationsSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = __DIR__.'/../Data';
        $governorateRows = $this->extractPhpMyAdminData($dataPath.'/egypt-governorates.json');
        $cityRows = $this->extractPhpMyAdminData($dataPath.'/egypt-cities.json');

        $legacyToUuid = [];

        foreach ($governorateRows as $row) {
            $code = Str::slug($row['governorate_name_en']);

            $governorate = Governorate::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name_ar' => $row['governorate_name_ar'],
                    'name_en' => $row['governorate_name_en'],
                    'is_active' => true,
                ],
            );

            $legacyToUuid[(string) $row['id']] = $governorate->governorate_id;
        }

        foreach ($cityRows as $row) {
            $legacyGovernorateId = (string) $row['governorate_id'];

            if (! isset($legacyToUuid[$legacyGovernorateId])) {
                continue;
            }

            $governorate = Governorate::query()->find($legacyToUuid[$legacyGovernorateId]);
            $govCode = $governorate?->code ?? 'gov';
            $citySlug = Str::slug($row['city_name_en']) ?: 'city-'.$row['id'];
            $code = $this->uniqueCityCode($govCode.'-'.$citySlug);

            City::query()->updateOrCreate(
                ['code' => $code],
                [
                    'governorate_id' => $legacyToUuid[$legacyGovernorateId],
                    'name_ar' => $row['city_name_ar'],
                    'name_en' => $row['city_name_en'],
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractPhpMyAdminData(string $path): array
    {
        if (! File::exists($path)) {
            throw new \RuntimeException("Location seed file not found: {$path}");
        }

        $decoded = json_decode(File::get($path), true);

        if (! is_array($decoded)) {
            return [];
        }

        foreach ($decoded as $entry) {
            if (is_array($entry) && ($entry['type'] ?? null) === 'table' && isset($entry['data'])) {
                return $entry['data'];
            }
        }

        return $decoded;
    }

    private function uniqueCityCode(string $base): string
    {
        $code = $base;
        $suffix = 1;

        while (City::query()->where('code', $code)->exists()) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }
}
