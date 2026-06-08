<?php

namespace App\Modules\AuditLog\Infrastructure\Database\Models;

use App\Modules\AuditLog\Domain\Enums\AuditActorTypeEnum;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'actor_type',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'metadata',
        'description',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event'      => AuditEventEnum::class,
            'actor_type' => AuditActorTypeEnum::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
