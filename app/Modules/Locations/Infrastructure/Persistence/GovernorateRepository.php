<?php

namespace App\Modules\Locations\Infrastructure\Persistence;

use App\Modules\Locations\Domain\Interfaces\GovernorateRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\Governorate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GovernorateRepository implements GovernorateRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Governorate::query()
            ->with('cities')
            ->withCount('cities')
            ->orderBy('name_ar');

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

    public function findById(string $governorateId): ?Governorate
    {
        return Governorate::query()
            ->with('cities')
            ->withCount('cities')
            ->find($governorateId);
    }

    public function create(array $data): Governorate
    {
        return Governorate::query()->create($data);
    }

    public function update(Governorate $governorate, array $data): Governorate
    {
        $governorate->update($data);

        return $governorate->fresh()->loadCount('cities');
    }

    public function delete(Governorate $governorate): void
    {
        $governorate->update(['is_active' => false]);
        $governorate->delete();
    }

    public function hasActiveCities(string $governorateId): bool
    {
        return Governorate::query()
            ->whereKey($governorateId)
            ->whereHas('cities', fn ($q) => $q->whereNull('deleted_at'))
            ->exists();
    }

    public function isGovernorateReferenced(string $governorateId): bool
    {
        $tables = ['order_addresses', 'shipping_company_addresses', 'agent_zones'];

        foreach ($tables as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }

            if (DB::table($table)->where('governorate_id', $governorateId)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function codeExists(string $code, ?string $exceptGovernorateId = null): bool
    {
        $query = Governorate::query()->where('code', $code);

        if ($exceptGovernorateId !== null) {
            $query->where('governorate_id', '!=', $exceptGovernorateId);
        }

        return $query->exists();
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
}
