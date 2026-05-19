<?php

namespace App\Modules\Core\Infrastructure\Persistence\Models;

use App\Modules\Core\Infrastructure\Services\MediaStorageService;
use App\Modules\Core\Infrastructure\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaFile extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table;

    protected $primaryKey = 'media_file_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'model_type',
        'model_id',
        'disk',
        'file_path',
        'file_size',
        'collection',
        'mime_type',
        'original_name',
        'tenant_id',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    protected $appends = ['url'];

    public function __construct(array $attributes = [])
    {
        $this->table = config('core.media.table', 'media_files');
        parent::__construct($attributes);
    }

    public function model(): MorphTo
    {
        return $this->morphTo(
            __FUNCTION__,
            config('core.media.morph_type'),
            config('core.media.morph_id')
        );
    }

    public function getUrlAttribute(): ?string
    {
        if (empty($this->file_path)) {
            return null;
        }

        return app(MediaStorageService::class)->url(
            $this->{config('core.media.disk_column', 'disk')},
            $this->{config('core.media.path_column', 'file_path')}
        );
    }
}
