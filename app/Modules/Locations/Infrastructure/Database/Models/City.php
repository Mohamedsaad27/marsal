<?php

namespace App\Modules\Locations\Infrastructure\Database\Models;

use App\Modules\Core\Infrastructure\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'cities';

    protected $primaryKey = 'city_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'governorate_id',
        'name_ar',
        'name_en',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class, 'governorate_id', 'governorate_id');
    }
}
