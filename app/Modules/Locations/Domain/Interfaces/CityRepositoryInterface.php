<?php

namespace App\Modules\Locations\Domain\Interfaces;

use App\Modules\Locations\Infrastructure\Database\Models\City;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CityRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findById(string $cityId): ?City;

    public function create(array $data): City;

    public function update(City $city, array $data): City;

    public function toggleStatus(City $city): City;

    public function delete(City $city): void;

    public function isCityReferenced(string $cityId): bool;

    public function codeExists(string $code, ?string $exceptCityId = null): bool;

    /**
     * @return array{total_cities:int,total_active:int,total_covered_governorates:int}
     */
    public function listKpis(): array;
}
