<?php

namespace App\Modules\Orders\Infrastructure\Database\Models;

use App\Modules\Core\Infrastructure\Traits\HasUuid;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostponedSchedule extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'postponed_schedules';

    protected $primaryKey = 'postponed_schedule_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'postponed_schedule_id',
        'order_id',
        'delivery_agent_id',
        'scheduled_date',
        'reason',
        'reminder_sent',
        'is_reassigned',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'reminder_sent' => 'boolean',
            'is_reassigned' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function deliveryAgent(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgent::class, 'delivery_agent_id', 'delivery_agent_id');
    }
}
