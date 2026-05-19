<?php

namespace App\Modules\Users\Infrastructure\Database\Models;

use App\Modules\Core\Infrastructure\Traits\HasUuid;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use HasRoles;
    use HasUuid;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'users';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'phone',
        'avatar',
        'gender',
        'is_active',
        'fcm_token',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'fcm_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'user_type' => AccountTypeEnum::class,
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'account_type' => $this->user_type?->code(),
            'roles' => $this->getRoleNames()->values()->all(),
            'permissions' => $this->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }

    public function shippingCompany(): HasOne
    {
        return $this->hasOne(ShippingCompany::class, 'user_id', 'user_id');
    }

    public function deliveryAgent(): HasOne
    {
        return $this->hasOne(DeliveryAgent::class, 'user_id', 'user_id');
    }

    protected function getDefaultGuardName(): string
    {
        return 'api';
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
