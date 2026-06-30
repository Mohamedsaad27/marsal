<?php

namespace App\Modules\Dashboard\Infrastructure\Database\Models;

use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'orders';

    protected $primaryKey = 'order_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'reference_no',
        'reference_code',
        'shipping_company_id',
        'delivery_agent_id',
        'status',
        'notes',
        'display_company_name',
        'assigned_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatusEnum::class,
            'assigned_at' => 'datetime',
            'delivered_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function customerInfo(): HasOne
    {
        return $this->hasOne(OrderCustomerInfo::class, 'order_id', 'order_id');
    }

    public function financials(): HasOne
    {
        return $this->hasOne(OrderFinancial::class, 'order_id', 'order_id');
    }

    public function address(): HasOne
    {
        return $this->hasOne(OrderAddress::class, 'order_id', 'order_id');
    }

    public function deliveryAgent(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgent::class, 'delivery_agent_id', 'delivery_agent_id');
    }

    public function shippingCompany(): BelongsTo
    {
        return $this->belongsTo(ShippingCompany::class, 'shipping_company_id', 'shipping_company_id');
    }
}
