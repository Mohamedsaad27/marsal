<?php

namespace App\Modules\Core\Domain\Interfaces;

use App\Modules\Core\Infrastructure\Persistence\Models\MediaFile;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

/**
 * Contract for models that store files via the unified media_files table.
 *
 * Implement this interface and use the HandlesMedia trait on the model.
 */
interface HasMediaFiles
{
    /**
     * Whitelisted owner key from config('core.media.allowed_owner_types').
     */
    public function getMediaOwnerType(): string;

    public function mediaFiles(): MorphMany;

    public function addMedia(
        UploadedFile $file,
        ?string $collection = 'default',
        ?string $disk = null,
    ): MediaFile;

    /**
     * @param  array<int, UploadedFile>  $files
     * @return Collection<int, MediaFile>
     */
    public function syncMedia(array $files, ?string $collection = 'default'): Collection;

    /**
     * @return Collection<int, MediaFile>
     */
    public function getMedia(?string $collection = null): Collection;

    public function getFirstMedia(?string $collection = 'default'): ?MediaFile;

    public function getMediaUrl(?string $collection = 'default'): ?string;

    public function clearMedia(?string $collection = null): void;

    public function deleteMedia(MediaFile $mediaFile): void;
}
