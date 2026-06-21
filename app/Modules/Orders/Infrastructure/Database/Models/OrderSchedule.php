<?php

namespace App\Modules\Orders\Infrastructure\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderSchedule extends Model
{
    use SoftDeletes;

    protected $table = 'order_schedules';

    protected $primaryKey = 'order_schedule_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'expected_delivery_date',
        'postponed_date',
        'schedule_notes',
    ];

    protected function casts(): array
    {
        return [
            'expected_delivery_date' => 'date',
            'postponed_date' => 'date',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
