<?php

namespace App\Modules\Collections\Infrastructure\Database\Models;

use App\Modules\Collections\Domain\Enums\SettlementTypeEnum;
use App\Modules\Core\Infrastructure\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementItem extends Model
{
    use HasUuid;

    protected $table = 'settlement_items';

    protected $primaryKey = 'settlement_item_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'settlement_item_id',
        'settlement_id',
        'collection_id',
        'settlement_type',
        'gross_amount',
        'commission_amount',
        'net_amount',
    ];

    protected function casts(): array
    {
        return [
            'settlement_type' => SettlementTypeEnum::class,
            'gross_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class, 'settlement_id', 'settlement_id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id', 'collection_id');
    }
}
