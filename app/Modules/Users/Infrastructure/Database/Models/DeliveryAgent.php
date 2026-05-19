<?php

namespace App\Modules\Users\Infrastructure\Database\Models;

use App\Modules\Core\Infrastructure\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryAgent extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'delivery_agents';

    protected $primaryKey = 'delivery_agent_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'national_id',
        'vehicle_type',
        'vehicle_plate_number',
        'commission_type',
        'commission_value',
        'balance',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'commission_value' => 'decimal:4',
            'balance' => 'decimal:2',
            'is_available' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
