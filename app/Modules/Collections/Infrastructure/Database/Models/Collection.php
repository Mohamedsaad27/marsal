<?php

namespace App\Modules\Collections\Infrastructure\Database\Models;

use App\Modules\Collections\Domain\Enums\CollectionTypeEnum;
use App\Modules\Core\Infrastructure\Traits\HasUuid;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'collections';

    protected $primaryKey = 'collection_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'collection_id',
        'order_id',
        'delivery_agent_id',
        'shipping_company_id',
        'collection_type',
        'collected_amount',
        'commission_amount',
        'net_due',
        'settlement_id',
        'cash_received_at',
        'cash_received_by',
        'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'collection_type' => CollectionTypeEnum::class,
            'collected_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'net_due' => 'decimal:2',
            'cash_received_at' => 'datetime',
            'collected_at' => 'datetime',
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

    public function shippingCompany(): BelongsTo
    {
        return $this->belongsTo(ShippingCompany::class, 'shipping_company_id', 'shipping_company_id');
    }

    public function cashReceivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cash_received_by', 'user_id');
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class, 'settlement_id', 'settlement_id');
    }
}
