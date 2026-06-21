<?php

namespace App\Modules\Orders\Infrastructure\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderApproval extends Model
{
    use SoftDeletes;

    protected $table = 'order_approvals';

    protected $primaryKey = 'order_approval_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'requires_approval',
        'approval_granted',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'requires_approval' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
