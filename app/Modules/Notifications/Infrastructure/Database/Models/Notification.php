<?php

namespace App\Modules\Notifications\Infrastructure\Database\Models;

use App\Modules\Core\Infrastructure\Traits\HasUuid;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasUuid;

    protected $table = 'notifications';

    protected $primaryKey = 'notification_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'notification_id',
        'user_id',
        'notification_type',
        'title_ar',
        'body_ar',
        'data',
        'is_read',
        'read_at',
        'sent_via_fcm',
        'fcm_message_id',
    ];

    protected function casts(): array
    {
        return [
            'notification_type' => NotificationTypeEnum::class,
            'data'              => 'array',
            'is_read'           => 'boolean',
            'read_at'           => 'datetime',
            'sent_via_fcm'      => 'boolean',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }

    // ── Relationships ───────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
