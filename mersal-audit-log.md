# Mersal — Full Audit Log Implementation

> Follows CLAUDE.md architecture rules strictly:
> Clean Architecture · UUID PKs · BIGINT on audit table · TINYINT enums · No Laravel ENUMs ·
> Repository pattern · UseCase pattern · ApiResponse helper · No logic in controllers

---

## 1. Migration

```php
<?php
// database/migrations/2026_06_08_000001_create_audit_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();                                                    // BIGINT UNSIGNED — high-volume, no UUID needed
            $table->char('user_id', 36)->nullable()->index('idx_al_user');  // who performed the action (null = system)
            $table->unsignedTinyInteger('actor_type')                       // 1=super_admin|2=shipping_company|3=delivery_agent|4=system
                  ->default(1)
                  ->comment('1=super_admin|2=shipping_company|3=delivery_agent|4=system');
            $table->unsignedTinyInteger('event')                            // see AuditEventEnum
                  ->comment('1=created|2=updated|3=deleted|4=restored|5=login|6=logout|7=status_changed|8=assigned|9=approved|10=rejected|11=settled|12=collected|13=returned|14=exported|15=password_changed|16=activated|17=deactivated');
            $table->string('auditable_type', 100)->index('idx_al_type');    // e.g. "orders", "users", "settlements"
            $table->char('auditable_id', 36)->index('idx_al_id');           // the affected record's PK (stored as string — covers both UUID & BIGINT)
            $table->json('old_values')->nullable();                          // snapshot before change
            $table->json('new_values')->nullable();                          // snapshot after change
            $table->json('metadata')->nullable();                            // extra context: ip, user_agent, order_ref, etc.
            $table->string('ip_address', 45)->nullable();                    // IPv4 or IPv6
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->useCurrent()->index('idx_al_created');

            // Composite index for the most common dashboard query: "all actions on this record"
            $table->index(['auditable_type', 'auditable_id'], 'idx_al_subject');
            // Composite index for actor timeline: "everything this user did"
            $table->index(['user_id', 'created_at'], 'idx_al_actor_timeline');
            // Composite index for event filtering: "all login events"
            $table->index(['event', 'created_at'], 'idx_al_event_timeline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

---

## 2. Domain Enum

```php
<?php
// app/Modules/AuditLog/Domain/Enums/AuditEventEnum.php

namespace App\Modules\AuditLog\Domain\Enums;

enum AuditEventEnum: int
{
    case Created         = 1;
    case Updated         = 2;
    case Deleted         = 3;
    case Restored        = 4;
    case Login           = 5;
    case Logout          = 6;
    case StatusChanged   = 7;
    case Assigned        = 8;
    case Approved        = 9;
    case Rejected        = 10;
    case Settled         = 11;
    case Collected       = 12;
    case Returned        = 13;
    case Exported        = 14;
    case PasswordChanged = 15;
    case Activated       = 16;
    case Deactivated     = 17;

    public function labelAr(): string
    {
        return match($this) {
            self::Created         => 'تم الإنشاء',
            self::Updated         => 'تم التعديل',
            self::Deleted         => 'تم الحذف',
            self::Restored        => 'تم الاسترجاع',
            self::Login           => 'تسجيل دخول',
            self::Logout          => 'تسجيل خروج',
            self::StatusChanged   => 'تغيير الحالة',
            self::Assigned        => 'تم التعيين',
            self::Approved        => 'تمت الموافقة',
            self::Rejected        => 'تم الرفض',
            self::Settled         => 'تمت التسوية',
            self::Collected       => 'تم التحصيل',
            self::Returned        => 'تم الإرجاع',
            self::Exported        => 'تم التصدير',
            self::PasswordChanged => 'تغيير كلمة المرور',
            self::Activated       => 'تم التفعيل',
            self::Deactivated     => 'تم التعطيل',
        };
    }
}
```

```php
<?php
// app/Modules/AuditLog/Domain/Enums/AuditActorTypeEnum.php

namespace App\Modules\AuditLog\Domain\Enums;

enum AuditActorTypeEnum: int
{
    case SuperAdmin       = 1;
    case ShippingCompany  = 2;
    case DeliveryAgent    = 3;
    case System           = 4;
}
```

---

## 3. Model

```php
<?php
// app/Modules/AuditLog/Infrastructure/Database/Models/AuditLog.php

namespace App\Modules\AuditLog\Infrastructure\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\AuditLog\Domain\Enums\AuditActorTypeEnum;

class AuditLog extends Model
{
    public $timestamps = false;     // only created_at, managed via useCurrent()

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
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'event'      => AuditEventEnum::class,
        'actor_type' => AuditActorTypeEnum::class,
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(
            \App\Modules\Users\Infrastructure\Database\Models\User::class,
            'user_id',
            'user_id'
        );
    }
}
```

---

## 4. Auditable Trait

Drop this trait on every model you want auto-audited (created / updated / deleted / restored).

```php
<?php
// app/Modules/AuditLog/Infrastructure/Traits/Auditable.php

namespace App\Modules\AuditLog\Infrastructure\Traits;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;

trait Auditable
{
    /**
     * Columns that should NEVER appear in old_values / new_values.
     * Override per model: protected array $auditExclude = ['secret_field'];
     */
    protected array $auditExclude = [
        'password', 'remember_token', 'fcm_token', 'email_verified_at',
    ];

    public static function bootAuditable(): void
    {
        static::created(fn($model) => $model->recordAudit(AuditEventEnum::Created, [], $model->toAuditArray()));
        static::updated(fn($model) => $model->recordAudit(AuditEventEnum::Updated, $model->toOldAuditArray(), $model->toAuditArray()));
        static::deleted(fn($model) => $model->recordAudit(AuditEventEnum::Deleted, $model->toAuditArray(), []));

        // SoftDeletes restore hook
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(fn($model) => $model->recordAudit(AuditEventEnum::Restored, [], $model->toAuditArray()));
        }
    }

    private function recordAudit(AuditEventEnum $event, array $oldValues, array $newValues): void
    {
        try {
            app(RecordAuditUseCase::class)->execute(
                userId:        auth()->id(),
                event:         $event,
                auditableType: $this->getTable(),
                auditableId:   (string) $this->getKey(),
                oldValues:     $oldValues,
                newValues:     $newValues,
            );
        } catch (\Throwable) {
            // Audit must never break the main flow
        }
    }

    private function toAuditArray(): array
    {
        return array_diff_key(
            $this->getAttributes(),
            array_flip($this->auditExclude)
        );
    }

    private function toOldAuditArray(): array
    {
        $old = [];
        foreach ($this->getChanges() as $key => $newVal) {
            if (in_array($key, $this->auditExclude)) {
                continue;
            }
            $old[$key] = $this->getOriginal($key);
        }
        return $old;
    }
}
```

**Add to models:**

```php
// e.g. Order.php, User.php, Settlement.php, etc.
use App\Modules\AuditLog\Infrastructure\Traits\Auditable;

class Order extends Model
{
    use Auditable;
    // ...
}
```

---

## 5. Repository Interface

```php
<?php
// app/Modules/AuditLog/Domain/Interfaces/AuditLogRepositoryInterface.php

namespace App\Modules\AuditLog\Domain\Interfaces;

use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditLogRepositoryInterface
{
    public function record(
        ?string        $userId,
        int            $actorType,
        AuditEventEnum $event,
        string         $auditableType,
        string         $auditableId,
        array          $oldValues,
        array          $newValues,
        array          $metadata,
        ?string        $ipAddress,
        ?string        $userAgent,
    ): void;

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function forSubject(string $auditableType, string $auditableId, int $perPage = 20): LengthAwarePaginator;
}
```

---

## 6. Repository Implementation

```php
<?php
// app/Modules/AuditLog/Infrastructure/Persistence/AuditLogRepository.php

namespace App\Modules\AuditLog\Infrastructure\Persistence;

use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\AuditLog\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Modules\AuditLog\Infrastructure\Database\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function record(
        ?string        $userId,
        int            $actorType,
        AuditEventEnum $event,
        string         $auditableType,
        string         $auditableId,
        array          $oldValues,
        array          $newValues,
        array          $metadata,
        ?string        $ipAddress,
        ?string        $userAgent,
    ): void {
        AuditLog::create([
            'user_id'        => $userId,
            'actor_type'     => $actorType,
            'event'          => $event->value,
            'auditable_type' => $auditableType,
            'auditable_id'   => $auditableId,
            'old_values'     => $oldValues ?: null,
            'new_values'     => $newValues ?: null,
            'metadata'       => $metadata  ?: null,
            'ip_address'     => $ipAddress,
            'user_agent'     => $userAgent,
            'created_at'     => now(),
        ]);
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = AuditLog::with('actor')
            ->when($filters['user_id']        ?? null, fn($q, $v) => $q->where('user_id', $v))
            ->when($filters['event']           ?? null, fn($q, $v) => $q->where('event', $v))
            ->when($filters['auditable_type']  ?? null, fn($q, $v) => $q->where('auditable_type', $v))
            ->when($filters['auditable_id']    ?? null, fn($q, $v) => $q->where('auditable_id', $v))
            ->when($filters['date_from']       ?? null, fn($q, $v) => $q->where('created_at', '>=', $v))
            ->when($filters['date_to']         ?? null, fn($q, $v) => $q->where('created_at', '<=', $v))
            ->orderByDesc('created_at');

        return $query->paginate($perPage);
    }

    public function forSubject(string $auditableType, string $auditableId, int $perPage = 20): LengthAwarePaginator
    {
        return AuditLog::with('actor')
            ->where('auditable_type', $auditableType)
            ->where('auditable_id', $auditableId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
```

---

## 7. Use Cases

### RecordAuditUseCase (called by trait + manually)

```php
<?php
// app/Modules/AuditLog/Application/UseCases/RecordAuditUseCase.php

namespace App\Modules\AuditLog\Application\UseCases;

use App\Modules\AuditLog\Domain\Enums\AuditActorTypeEnum;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\AuditLog\Domain\Interfaces\AuditLogRepositoryInterface;

class RecordAuditUseCase
{
    public function __construct(
        private AuditLogRepositoryInterface $repository,
    ) {}

    public function execute(
        ?string        $userId,
        AuditEventEnum $event,
        string         $auditableType,
        string         $auditableId,
        array          $oldValues = [],
        array          $newValues = [],
        array          $metadata  = [],
    ): void {
        $this->repository->record(
            userId:        $userId,
            actorType:     $this->resolveActorType(),
            event:         $event,
            auditableType: $auditableType,
            auditableId:   $auditableId,
            oldValues:     $oldValues,
            newValues:     $newValues,
            metadata:      $metadata,
            ipAddress:     request()->ip(),
            userAgent:     request()->userAgent(),
        );
    }

    private function resolveActorType(): int
    {
        if (! $user = auth()->user()) {
            return AuditActorTypeEnum::System->value;
        }

        // Matches the role codes in CLAUDE.md section 2
        return match($user->roles->first()?->name) {
            'super_admin'       => AuditActorTypeEnum::SuperAdmin->value,
            'shipping_company'  => AuditActorTypeEnum::ShippingCompany->value,
            'delivery_agent'    => AuditActorTypeEnum::DeliveryAgent->value,
            default             => AuditActorTypeEnum::System->value,
        };
    }
}
```

### GetAuditLogsUseCase (admin list + subject history)

```php
<?php
// app/Modules/AuditLog/Application/UseCases/GetAuditLogsUseCase.php

namespace App\Modules\AuditLog\Application\UseCases;

use App\Modules\AuditLog\Domain\Interfaces\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetAuditLogsUseCase
{
    public function __construct(
        private AuditLogRepositoryInterface $repository,
    ) {}

    public function execute(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function forSubject(string $auditableType, string $auditableId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->forSubject($auditableType, $auditableId, $perPage);
    }
}
```

---

## 8. API Resource

```php
<?php
// app/Modules/AuditLog/Presentation/Http/Resources/AuditLogResource.php

namespace App\Modules\AuditLog\Presentation\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'event'          => [
                'code'  => $this->event->value,
                'label' => $this->event->labelAr(),
            ],
            'actor_type'     => $this->actor_type->value,
            'actor'          => $this->when(
                $this->relationLoaded('actor') && $this->actor,
                fn() => [
                    'id'    => $this->actor->user_id,
                    'name'  => $this->actor->name,
                    'phone' => $this->actor->phone,
                ]
            ),
            'auditable_type' => $this->auditable_type,
            'auditable_id'   => $this->auditable_id,
            'old_values'     => $this->old_values,
            'new_values'     => $this->new_values,
            'metadata'       => $this->metadata,
            'ip_address'     => $this->ip_address,
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
```

---

## 9. Form Requests

```php
<?php
// app/Modules/AuditLog/Presentation/Http/Requests/IndexAuditLogRequest.php

namespace App\Modules\AuditLog\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;

class IndexAuditLogRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $validEvents = implode(',', array_column(AuditEventEnum::cases(), 'value'));

        return [
            'user_id'       => ['nullable', 'uuid'],
            'event'         => ['nullable', 'integer', "in:{$validEvents}"],
            'auditable_type'=> ['nullable', 'string', 'max:100'],
            'auditable_id'  => ['nullable', 'string', 'max:36'],
            'date_from'     => ['nullable', 'date'],
            'date_to'       => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page'      => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
```

---

## 10. Controller

```php
<?php
// app/Modules/AuditLog/Presentation/Http/Controllers/AuditLogController.php

namespace App\Modules\AuditLog\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AuditLog\Application\UseCases\GetAuditLogsUseCase;
use App\Modules\AuditLog\Presentation\Http\Requests\IndexAuditLogRequest;
use App\Modules\AuditLog\Presentation\Http\Resources\AuditLogResource;
use App\Modules\Core\Application\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    public function __construct(
        private GetAuditLogsUseCase $getAuditLogs,
    ) {}

    /**
     * GET /api/v1/admin/audit-logs
     */
    public function index(IndexAuditLogRequest $request): JsonResponse
    {
        $logs = $this->getAuditLogs->execute(
            $request->validated(),
            $request->integer('per_page', 20)
        );

        return ApiResponse::paginated(AuditLogResource::collection($logs));
    }

    /**
     * GET /api/v1/admin/audit-logs/{type}/{id}
     * e.g. /api/v1/admin/audit-logs/orders/uuid-here
     */
    public function forSubject(string $auditableType, string $auditableId): JsonResponse
    {
        $logs = $this->getAuditLogs->forSubject($auditableType, $auditableId);

        return ApiResponse::paginated(AuditLogResource::collection($logs));
    }
}
```

---

## 11. Routes

```php
<?php
// app/Modules/AuditLog/Presentation/Routes/api.php

use App\Modules\AuditLog\Presentation\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->prefix('api/v1/admin')->group(function () {
    Route::get('audit-logs',               [AuditLogController::class, 'index']);
    Route::get('audit-logs/{type}/{id}',   [AuditLogController::class, 'forSubject']);
});
```

---

## 12. Service Provider

```php
<?php
// app/Modules/AuditLog/Infrastructure/Providers/AuditLogServiceProvider.php

namespace App\Modules\AuditLog\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\AuditLog\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Modules\AuditLog\Infrastructure\Persistence\AuditLogRepository;

class AuditLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
    }
}
```

Register in `bootstrap/providers.php`:

```php
App\Modules\AuditLog\Infrastructure\Providers\AuditLogServiceProvider::class,
```

---

## 13. Manual Audit — High-Value Events

For business events that aren't simple model saves (login, status change, approval), call `RecordAuditUseCase` manually inside the relevant Use Case:

```php
// Inside LoginUseCase — after issuing the JWT
$this->recordAudit->execute(
    userId:        $user->user_id,
    event:         AuditEventEnum::Login,
    auditableType: 'users',
    auditableId:   $user->user_id,
    metadata:      ['ip' => request()->ip()],
);

// Inside UpdateOrderStatusUseCase — before updating
$this->recordAudit->execute(
    userId:        auth()->id(),
    event:         AuditEventEnum::StatusChanged,
    auditableType: 'orders',
    auditableId:   $order->id,
    oldValues:     ['status_id' => $order->status_id->value],
    newValues:     ['status_id' => $newStatus->value],
    metadata:      ['order_ref' => $order->internal_code],
);

// Inside ApprovalRequestUseCase — on approve/reject
$this->recordAudit->execute(
    userId:        auth()->id(),
    event:         $approved ? AuditEventEnum::Approved : AuditEventEnum::Rejected,
    auditableType: 'approval_requests',
    auditableId:   $approvalRequest->approval_request_id,
    metadata:      ['order_id' => $approvalRequest->order_id],
);

// Inside SettlementUseCase — on mark-as-paid
$this->recordAudit->execute(
    userId:        auth()->id(),
    event:         AuditEventEnum::Settled,
    auditableType: 'settlements',
    auditableId:   $settlement->id,
    metadata:      ['amount' => $settlement->total_amount, 'type' => $settlement->settlement_type],
);
```

---

## 14. Language Files

```php
<?php
// app/Modules/AuditLog/Presentation/Resources/lang/ar/audit_logs.php
return [
    'fetched'  => 'تم جلب سجلات التدقيق',
    'not_found'=> 'سجل التدقيق غير موجود',
];
```

```php
<?php
// app/Modules/AuditLog/Presentation/Resources/lang/en/audit_logs.php
return [
    'fetched'  => 'Audit logs fetched successfully',
    'not_found'=> 'Audit log not found',
];
```

---

## 15. Which Models Get the `Auditable` Trait

| Model | Table | Why |
|---|---|---|
| `User` | users | account changes, activation |
| `Order` | orders | assignment, status |
| `OrderFinancial` | order_financials | amount changes |
| `Collection` | collections | cash records |
| `Return` | returns | status changes |
| `Settlement` | settlements | financial clearance |
| `ApprovalRequest` | approval_requests | approve/reject trail |
| `DeliveryAgent` | delivery_agents | profile changes |
| `ShippingCompany` | shipping_companies | profile, balance |
| `SystemSetting` | system_settings | config changes |

Login / Logout / StatusChanged / Assigned → manual call in their Use Cases (not model-level).

---

## 16. Module Directory Layout

```
app/Modules/AuditLog/
├── Application/
│   └── UseCases/
│       ├── RecordAuditUseCase.php
│       └── GetAuditLogsUseCase.php
├── Domain/
│   ├── Enums/
│   │   ├── AuditEventEnum.php
│   │   └── AuditActorTypeEnum.php
│   └── Interfaces/
│       └── AuditLogRepositoryInterface.php
├── Infrastructure/
│   ├── Database/
│   │   └── Models/
│   │       └── AuditLog.php
│   ├── Persistence/
│   │   └── AuditLogRepository.php
│   ├── Providers/
│   │   └── AuditLogServiceProvider.php
│   └── Traits/
│       └── Auditable.php
└── Presentation/
    ├── Http/
    │   ├── Controllers/
    │   │   └── AuditLogController.php
    │   ├── Requests/
    │   │   └── IndexAuditLogRequest.php
    │   └── Resources/
    │       └── AuditLogResource.php
    ├── Resources/
    │   └── lang/
    │       ├── ar/audit_logs.php
    │       └── en/audit_logs.php
    └── Routes/
        └── api.php
```

---

## 17. API Contract

### GET `/api/v1/admin/audit-logs`

**Query params:** `user_id`, `event`, `auditable_type`, `auditable_id`, `date_from`, `date_to`, `per_page`

**Response:**
```json
{
  "status": true,
  "message": "تم جلب سجلات التدقيق",
  "data": [
    {
      "id": 1042,
      "event": { "code": 7, "label": "تغيير الحالة" },
      "actor_type": 3,
      "actor": { "id": "uuid", "name": "أحمد مندوب", "phone": "01000000000" },
      "auditable_type": "orders",
      "auditable_id": "order-uuid",
      "old_values": { "status_id": 3 },
      "new_values": { "status_id": 5 },
      "metadata": { "order_ref": "MRS-0042" },
      "ip_address": "197.0.1.1",
      "created_at": "2026-06-08T10:30:00.000000Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 10, "per_page": 20, "total": 192, "has_more": true }
}
```

### GET `/api/v1/admin/audit-logs/orders/{order_id}`

Returns paginated audit trail for a single order — same shape as above.
