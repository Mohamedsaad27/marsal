<?php

namespace App\Modules\Core\Presentation\Http\Resources;

use App\Modules\Core\Infrastructure\Persistence\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MediaFile */
class MediaFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'media_file_id' => $this->media_file_id,
            'collection'    => $this->{config('core.media.collection')},
            'disk'          => $this->{config('core.media.disk_column')},
            'file_path'     => $this->{config('core.media.path_column')},
            'file_size'     => $this->{config('core.media.size_column')},
            'mime_type'     => $this->mime_type,
            'original_name' => $this->original_name,
            'url'           => $this->url,
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
