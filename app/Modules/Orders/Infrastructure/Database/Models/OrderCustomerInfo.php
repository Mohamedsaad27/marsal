<?php

namespace App\Modules\Orders\Infrastructure\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderCustomerInfo extends Model
{
    use SoftDeletes;

    protected $table = 'order_customer_info';

    protected $primaryKey = 'order_customer_info_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'customer_name',
        'customer_phone',
        'phone_alt',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
