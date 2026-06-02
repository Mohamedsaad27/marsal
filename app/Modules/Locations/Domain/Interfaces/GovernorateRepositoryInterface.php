<?php

namespace App\Modules\Locations\Domain\Interfaces;

use App\Modules\Locations\Infrastructure\Database\Models\Governorate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GovernorateRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findById(string $governorateId): ?Governorate;

    public function create(array $data): Governorate;

    public function update(Governorate $governorate, array $data): Governorate;

    public function delete(Governorate $governorate): void;

    public function hasActiveCities(string $governorateId): bool;

    public function isGovernorateReferenced(string $governorateId): bool;

    public function codeExists(string $code, ?string $exceptGovernorateId = null): bool;
}
