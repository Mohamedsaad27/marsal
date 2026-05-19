<?php

namespace App\Modules\Core\Infrastructure\Services;

use App\Modules\Core\Application\Exceptions\MediaUploadException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaStorageService
{
    public function store(
        UploadedFile $file,
        string $ownerType,
        string $ownerId,
        ?string $collection = 'default',
        ?string $disk = null,
    ): array {
        $disk = $disk ?? config('core.media.default_disk', 'public');
        $collection = $collection ?? 'default';

        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $filename = Str::uuid() . '.' . strtolower($extension);
        $directory = trim("media/{$ownerType}/{$ownerId}/{$collection}", '/');
        $path = "{$directory}/{$filename}";

        $stored = Storage::disk($disk)->putFileAs($directory, $file, $filename);

        if ($stored === false) {
            throw new MediaUploadException();
        }

        return [
            'disk' => $disk,
            'file_path' => $path,
            'file_size' => $file->getSize() ?: Storage::disk($disk)->size($path),
            'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
            'original_name' => $file->getClientOriginalName(),
        ];
    }

    public function delete(?string $disk, ?string $path): void
    {
        if ($disk === null || $path === null || $path === '') {
            return;
        }

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    public function url(?string $disk, ?string $path): ?string
    {
        if ($disk === null || $path === null || $path === '') {
            return null;
        }

        return Storage::disk($disk)->url($path);
    }
}
