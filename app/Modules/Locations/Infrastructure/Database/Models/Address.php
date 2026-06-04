<?php

namespace App\Modules\Locations\Infrastructure\Database\Models;

use App\Modules\Core\Infrastructure\Traits\HasUuid;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $table = 'addresses';

    protected $primaryKey = 'address_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'city_id',
        'address_line',
        'landmark',
        'street',
        'building_number',
        'floor_number',
        'apartment_number',
        'is_default',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id', 'city_id');
    }
}
