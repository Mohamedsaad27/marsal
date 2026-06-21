<?php

namespace App\Modules\Orders\Infrastructure\Database\Models;

use App\Modules\Orders\Domain\Enums\OrderProofFileTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderProof extends Model
{
    use SoftDeletes;

    protected $table = 'order_proofs';

    protected $primaryKey = 'order_proof_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'uploaded_by',
        'file_type',
        'file_url',
    ];

    protected function casts(): array
    {
        return [
            'file_type' => OrderProofFileTypeEnum::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'user_id');
    }
}
