<?php

namespace App\Modules\Orders\Infrastructure\Database\Models;

use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OrderStatusHistory extends Model
{
    use SoftDeletes;

    protected $table = 'order_status_history';

    protected $primaryKey = 'order_status_history_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'from_status_id',
        'to_status_id',
        'changed_by',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->order_status_history_id)) {
                $model->order_status_history_id = (string) Str::uuid();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by', 'user_id');
    }
}
