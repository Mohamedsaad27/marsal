<?php

namespace App\Modules\Chat\Infrastructure\Database\Models;

use App\Modules\Chat\Domain\Enums\MessageTypeEnum;
use App\Modules\Core\Domain\Interfaces\HasMediaFiles;
use App\Modules\Core\Infrastructure\Traits\HandlesMedia;
use App\Modules\Core\Infrastructure\Traits\HasUuid;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model implements HasMediaFiles
{
    use HandlesMedia;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'messages';

    protected $primaryKey = 'message_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'message_type',
    ];

    protected function casts(): array
    {
        return [
            'message_type' => MessageTypeEnum::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getMediaOwnerType(): string
    {
        return 'message';
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class, 'message_id', 'message_id');
    }
}
