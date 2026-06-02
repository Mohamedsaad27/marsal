<?php

namespace App\Modules\Locations\Infrastructure\Persistence;

use App\Modules\Locations\Domain\Interfaces\CityRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\City;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CityRepository implements CityRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = City::query()
            ->with('governorate')
            ->orderBy('name_ar');

        if (! empty($filters['governorate_id'])) {
            $query->where('governorate_id', $filters['governorate_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function findById(string $cityId): ?City
    {
        return City::query()
            ->with('governorate')
            ->find($cityId);
    }

    public function create(array $data): City
    {
        return City::query()->create($data)->load('governorate');
    }

    public function update(City $city, array $data): City
    {
        $city->update($data);

        return $city->fresh()->load('governorate');
    }

    public function delete(City $city): void
    {
        $city->update(['is_active' => false]);
        $city->delete();
    }

    public function isCityReferenced(string $cityId): bool
    {
        $tables = ['order_addresses', 'shipping_company_addresses', 'agent_zones'];

        foreach ($tables as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            if (DB::table($table)->where('city_id', $cityId)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function codeExists(string $code, ?string $exceptCityId = null): bool
    {
        $query = City::query()->where('code', $code);

        if ($exceptCityId !== null) {
            $query->where('city_id', '!=', $exceptCityId);
        }

        return $query->exists();
    }
}
