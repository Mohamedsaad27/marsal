<?php

namespace App\Modules\Auth\Infrastructure\Database\Models;

use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PasswordResetOtp extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'password_reset_otps';

    protected $fillable = [
        'user_id',
        'email',
        'otp_hash',
        'attempts',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function isUsable(): bool
    {
        return $this->used_at === null
            && $this->expires_at->isFuture()
            && $this->attempts < (int) config('auth_module.otp_max_attempts', 5);
    }
}
