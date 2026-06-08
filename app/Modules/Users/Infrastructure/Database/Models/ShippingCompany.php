<?php

namespace App\Modules\Users\Infrastructure\Database\Models;

use App\Modules\AuditLog\Infrastructure\Traits\Auditable;
use App\Modules\Core\Infrastructure\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingCompany extends Model
{
    use Auditable;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'shipping_companies';

    protected $primaryKey = 'shipping_company_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'company_name',
        'commercial_reg',
        'logo_url',
        'commission_type',
        'commission_value',
        'balance',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'commission_value' => 'decimal:4',
            'balance' => 'decimal:2',
            'is_active' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
