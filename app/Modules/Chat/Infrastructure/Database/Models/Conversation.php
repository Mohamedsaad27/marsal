<?php

namespace App\Modules\Chat\Infrastructure\Database\Models;

use App\Modules\Chat\Domain\Enums\ConversationTypeEnum;
use App\Modules\Core\Infrastructure\Traits\HasUuid;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'conversations';

    protected $primaryKey = 'conversation_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'conversation_type',
    ];

    protected function casts(): array
    {
        return [
            'conversation_type' => ConversationTypeEnum::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class, 'conversation_id', 'conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id', 'conversation_id');
    }
}
