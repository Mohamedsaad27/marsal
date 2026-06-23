<?php

namespace App\Modules\Orders\Infrastructure\Database\Models;

use App\Modules\Orders\Domain\Enums\ApprovalStatusEnum;
use App\Modules\Orders\Domain\Enums\ApprovalTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequest extends Model
{
    protected $table = 'approval_requests';

    protected $primaryKey = 'approval_request_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'approval_type',
        'approval_status',
        'requested_by',
        'reviewed_by',
        'original_amount',
        'requested_amount',
        'reason',
        'review_notes',
        'expires_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'approval_type'    => ApprovalTypeEnum::class,
            'approval_status'  => ApprovalStatusEnum::class,
            'original_amount'  => 'decimal:2',
            'requested_amount' => 'decimal:2',
            'expires_at'       => 'datetime',
            'reviewed_at'      => 'datetime',
            'created_at'       => 'datetime',
            'updated_at'       => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'user_id');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }
}
