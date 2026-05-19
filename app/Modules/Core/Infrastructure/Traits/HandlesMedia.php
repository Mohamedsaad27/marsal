<?php

namespace App\Modules\Core\Infrastructure\Traits;

use App\Modules\Core\Application\Exceptions\InvalidMediaOwnerTypeException;
use App\Modules\Core\Domain\Interfaces\HasMediaFiles;
use App\Modules\Core\Infrastructure\Helpers\TenantContext;
use App\Modules\Core\Infrastructure\Persistence\Models\MediaFile;
use App\Modules\Core\Infrastructure\Services\MediaStorageService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reusable media helpers for any Eloquent model with a morphMany media_files relation.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HandlesMedia
{
    public static function bootHandlesMedia(): void
    {
        static::deleting(function ($model) {
            if (! method_exists($model, 'clearMedia')) {
                return;
            }

            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->clearMedia();
        });
    }

    /**
     * Store the short owner key in model_type (not the FQCN).
     */
    public function getMorphClass(): string
    {
        return $this->getMediaOwnerType();
    }

    public function mediaFiles(): MorphMany
    {
        return $this->morphMany(
            MediaFile::class,
            'model',
            config('core.media.morph_type'),
            config('core.media.morph_id')
        );
    }

    public function addMedia(
        UploadedFile $file,
        ?string $collection = 'default',
        ?string $disk = null,
    ): MediaFile {
        $this->assertValidMediaOwnerType();

        $ownerType = $this->getMediaOwnerType();
        $stored = app(MediaStorageService::class)->store(
            $file,
            $ownerType,
            (string) $this->getKey(),
            $collection,
            $disk
        );

        return $this->mediaFiles()->create([
            config('core.media.disk_column') => $stored['disk'],
            config('core.media.path_column') => $stored['file_path'],
            config('core.media.size_column') => $stored['file_size'],
            config('core.media.collection') => $collection ?? 'default',
            'mime_type' => $stored['mime_type'],
            'original_name' => $stored['original_name'],
            'tenant_id' => TenantContext::get(),
        ]);
    }

    /**
     * Replace all media in a collection with the given uploaded files.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function syncMedia(array $files, ?string $collection = 'default'): Collection
    {
        return DB::transaction(function () use ($files, $collection) {
            $this->clearMedia($collection);

            return collect($files)->map(
                fn (UploadedFile $file) => $this->addMedia($file, $collection)
            );
        });
    }

    /**
     * @return Collection<int, MediaFile>
     */
    public function getMedia(?string $collection = null): Collection
    {
        $query = $this->mediaFiles();

        if ($collection !== null) {
            $query->where(config('core.media.collection'), $collection);
        }

        return $query->orderBy('created_at')->get();
    }

    public function getFirstMedia(?string $collection = 'default'): ?MediaFile
    {
        return $this->getMedia($collection)->first();
    }

    public function getMediaUrl(?string $collection = 'default'): ?string
    {
        return $this->getFirstMedia($collection)?->url;
    }

    public function clearMedia(?string $collection = null): void
    {
        $mediaFiles = $this->getMedia($collection);
        $storage = app(MediaStorageService::class);

        foreach ($mediaFiles as $mediaFile) {
            $storage->delete(
                $mediaFile->{config('core.media.disk_column')},
                $mediaFile->{config('core.media.path_column')}
            );
            $mediaFile->forceDelete();
        }
    }

    public function deleteMedia(MediaFile $mediaFile): void
    {
        if ($mediaFile->{config('core.media.morph_id')} !== (string) $this->getKey()
            || $mediaFile->{config('core.media.morph_type')} !== $this->getMediaOwnerType()) {
            return;
        }

        app(MediaStorageService::class)->delete(
            $mediaFile->{config('core.media.disk_column')},
            $mediaFile->{config('core.media.path_column')}
        );

        $mediaFile->forceDelete();
    }

    protected function assertValidMediaOwnerType(): void
    {
        $ownerType = $this->getMediaOwnerType();
        $allowed = config('core.media.allowed_owner_types', []);

        if (! in_array($ownerType, $allowed, true)) {
            throw new InvalidMediaOwnerTypeException($ownerType);
        }
    }
}
