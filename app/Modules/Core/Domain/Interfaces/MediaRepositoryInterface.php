<?php

namespace App\Modules\Core\Domain\Interfaces;

use App\Modules\Core\Infrastructure\Persistence\Models\MediaFile;
use Illuminate\Support\Collection;

interface MediaRepositoryInterface
{
    public function create(array $attributes): MediaFile;

    public function findById(string $mediaFileId): ?MediaFile;

    public function delete(MediaFile $mediaFile): bool;

    /**
     * @return Collection<int, MediaFile>
     */
    public function getForOwner(string $modelType, string $modelId, ?string $collection = null): Collection;

    public function deleteForOwner(string $modelType, string $modelId, ?string $collection = null): int;
}
