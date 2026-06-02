<?php

namespace App\Modules\Locations\Infrastructure\Database\Models;

use App\Modules\Core\Infrastructure\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Governorate extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'governorates';

    protected $primaryKey = 'governorate_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
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

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'governorate_id', 'governorate_id');
    }
}
