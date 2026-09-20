<?php

namespace App\Modules\Collections\Infrastructure\Database\Models;

use App\Modules\Collections\Domain\Enums\SettlementStatusEnum;
use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Core\Infrastructure\Traits\HasUuid;
use App\Modules\Users\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Settlement extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'settlements';

    protected $primaryKey = 'settlement_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'settlement_id',
        'settlement_type',
        'settlement_status',
        'delivery_agent_id',
        'shipping_company_id',
        'initiated_by',
        'total_collections',
        'total_commissions',
        'net_amount',
        'period_from',
        'period_to',
        'payment_method',
        'payment_reference',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'settlement_type' => SettlementTypeEnum::class,
            'settlement_status' => SettlementStatusEnum::class,
            'total_collections' => 'decimal:2',
            'total_commissions' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'period_from' => 'date',
            'period_to' => 'date',
            'paid_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function deliveryAgent(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgent::class, 'delivery_agent_id', 'delivery_agent_id');
    }

    public function shippingCompany(): BelongsTo
    {
        return $this->belongsTo(ShippingCompany::class, 'shipping_company_id', 'shipping_company_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by', 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SettlementItem::class, 'settlement_id', 'settlement_id');
    }

    public function paymentDirection(): string
    {
        $netAmount = round((float) $this->net_amount, 2);

        if ($netAmount === 0.0) {
            return 'no_payment';
        }

        return match ($this->settlement_type) {
            SettlementTypeEnum::Agent => $netAmount > 0
                ? 'agent_to_system'
                : 'system_to_agent',
            SettlementTypeEnum::Company => $netAmount > 0
                ? 'system_to_company'
                : 'company_to_system',
        };
    }

    public function payableAmount(): float
    {
        return abs(round((float) $this->net_amount, 2));
    }
}
