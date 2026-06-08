<?php

namespace App\Modules\Dashboard\Infrastructure\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderFinancial extends Model
{
    use SoftDeletes;

    protected $table = 'order_financials';

    protected $primaryKey = 'order_financial_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'original_amount',
        'approved_amount',
        'collected_amount',
        'shipping_fee',
        'commission_amount',
        'net_due_company',
        'is_settled',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'collected_amount' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'net_due_company' => 'decimal:2',
            'is_settled' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
