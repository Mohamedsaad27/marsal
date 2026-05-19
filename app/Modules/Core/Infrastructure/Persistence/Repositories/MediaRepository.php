<?php

namespace App\Modules\Core\Infrastructure\Persistence\Repositories;

use App\Modules\Core\Domain\Interfaces\MediaRepositoryInterface;
use App\Modules\Core\Infrastructure\Persistence\Models\MediaFile;
use Illuminate\Support\Collection;

class MediaRepository implements MediaRepositoryInterface
{
    public function create(array $attributes): MediaFile
    {
        return MediaFile::query()->create($attributes);
    }

    public function findById(string $mediaFileId): ?MediaFile
    {
        return MediaFile::query()->find($mediaFileId);
    }

    public function delete(MediaFile $mediaFile): bool
    {
        return (bool) $mediaFile->delete();
    }

    public function getForOwner(string $modelType, string $modelId, ?string $collection = null): Collection
    {
        $query = MediaFile::query()
            ->where(config('core.media.morph_type'), $modelType)
            ->where(config('core.media.morph_id'), $modelId);

        if ($collection !== null) {
            $query->where(config('core.media.collection'), $collection);
        }

        return $query->orderBy('created_at')->get();
    }

    public function deleteForOwner(string $modelType, string $modelId, ?string $collection = null): int
    {
        $query = MediaFile::query()
            ->where(config('core.media.morph_type'), $modelType)
            ->where(config('core.media.morph_id'), $modelId);

        if ($collection !== null) {
            $query->where(config('core.media.collection'), $collection);
        }

        return $query->delete();
    }
}
