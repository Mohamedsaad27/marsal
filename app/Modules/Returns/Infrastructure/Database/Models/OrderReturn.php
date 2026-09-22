<?php

namespace App\Modules\Returns\Infrastructure\Database\Models;

use App\Modules\Orders\Infrastructure\Database\Models\Order;
use App\Modules\Returns\Domain\Enums\ReturnStatusEnum;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderReturn extends Model
{
    use SoftDeletes;

    protected $table = 'returns';

    protected $primaryKey = 'return_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'delivery_agent_id',
        'shipping_company_id',
        'return_status',
        'returned_quantity',
        'return_reason',
        'received_at',
        'returned_to_company_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'return_status' => ReturnStatusEnum::class,
            'received_at' => 'datetime',
            'returned_to_company_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function deliveryAgent(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgent::class, 'delivery_agent_id', 'delivery_agent_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function shippingCompany(): BelongsTo
    {
        return $this->belongsTo(ShippingCompany::class, 'shipping_company_id', 'shipping_company_id');
    }
}
