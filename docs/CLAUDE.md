# Mersal — Claude AI Rules (Master File)

> **Read this file completely before starting any task.**
> Mersal is a B2B logistics and delivery management system built on Laravel + Flutter.
> Every rule here is mandatory. No exceptions unless explicitly stated per-task.
> The system connects shipping companies ↔ admin ↔ delivery agents.

---

## 1. Project Identity

| Key | Value |
|-----|-------|
| Product | Mersal |
| Market | Egyptian B2B logistics market |
| Backend | Laravel 13 — REST API |
| Frontend Dashboard | React (Admin web panel) |
| Mobile App | Flutter (Android + iOS — delivery agents) |
| Architecture | Modular Monolith — Module-based Clean Architecture |
| Auth | JWT (`tymon/jwt-auth ^2.3`) — guard: `api` |
| Authorization | Role-Based Access Control via `spatie/laravel-permission` (roles embedded in JWT claims) |
| Primary Keys | **UUID strings** — domain tables use named PKs (`user_id`, `order_id`, `notification_id`, etc.) via `HasUuid` trait |
| Language | Arabic (primary UI) + English (code & comments) — always add readable human messages |
| Realtime | Firebase (chat + notifications) |
| Push | Firebase Cloud Messaging (FCM) — custom HTTP implementation via `Illuminate\Support\Facades\Http`, no Composer FCM package |
| File Storage | Local storage (proof photos, attachments) via `media_files` table |
| Database | MySQL 8.0.30+ — single database, no multi-tenancy |

---

## 2. System Users — Never Confuse Them

| Account Type | Role Code | Who They Are | Interface |
|--------------|-----------|--------------|-----------|
| admin | `super_admin` | Internal admin team | Web Dashboard — Full Access |
| company | `shipping_company` | Client companies that send orders | Flutter Mobile App |
| agent | `delivery_agent` | Field delivery personnel | Flutter Mobile App |
| staff | `staff_member` | Internal company employees (sub-accounts) | Web Dashboard — Limited Access |

> **`account_type`** is a `TINYINT` column on the `users` table that determines which profile table to join for the extended data (`shipping_companies`, `delivery_agents`, `staff_members`).

### Business Flow (Read Before Every Task)

```
Shipping Company → creates orders via Flutter Mobile App
        ↓
Super Admin → receives, reviews, assigns to delivery agents
        ↓
Delivery Agent → attempts delivery → updates status via mobile app
        ↓
Agent collects cash (COD, shipping fee, or partial) when applicable
        ↓
Admin receives cash collections from agent
        ↓
Admin settles (transfers net amount) to shipping company
```

---

## 3. Directory Structure

### Actual Modules (what exists today)

```
app/
└── Modules/
    ├── Core/              ← Shared base classes, traits, helpers, module loader
    ├── Auth/              ← JWT login, logout, token refresh, OTP password reset
    ├── Users/             ← All user types: admin, companies, agents, staff
    │                        (includes shipping_companies, delivery_agents, staff_members tables)
    ├── Roles/             ← Spatie permissions wrapper — roles & permissions CRUD
    ├── Locations/         ← Governorates, cities, addresses
    ├── Departments/       ← Internal department management for staff
    ├── AuditLog/          ← Action audit trail (who did what, when)
    ├── Settings/          ← Runtime config key-value store (system_settings table)
    ├── Notifications/     ← FCM push + in-app notifications (fully built)
    ├── Dashboard/         ← Stats, charts, order models (Order model lives here temporarily)
    └── Orders/            ← Core order lifecycle (DB tables migrated, module is a stub — routes empty)
```

### DB Tables with No Module Yet (planned — migrations exist)

```
Finance:       collections, returns, settlements, approval_requests
Timer:         refusal_timer_logs
Chat:          conversations, conversation_participants, messages, message_reads
Schedule:      postponed_schedules
GPS:           (gps_checkpoints — planned, migration not yet created)
```

### Module Discovery

`CoreServiceProvider` registers `ModuleServiceProvider`, which auto-discovers modules by scanning `app/Modules/*/Infrastructure/Providers/{ModuleName}ServiceProvider.php`. Modules with a `module.json` file can declare extra providers to register.

### Internal Structure of Each Module (Clean Architecture)

```
Modules/{Name}/
│
├── Application/
│   ├── DTOs/
│   ├── Exceptions/
│   ├── Listeners/         ← Event listeners (if module handles domain events)
│   └── UseCases/
│
├── Domain/
│   ├── DTOs/              ← Value objects passed across layer boundaries
│   ├── Enums/             ← IntBackedEnum for all TINYINT coded columns
│   ├── Events/            ← Domain events (fired by Use Cases)
│   ├── Interfaces/        ← Repository interfaces
│   └── Services/          ← Pure domain logic
│
├── Infrastructure/
│   ├── Config/            ← Module-specific config files
│   ├── Database/
│   │   ├── Migrations/    ← Module-owned migrations (not all modules use this)
│   │   ├── Seeders/
│   │   └── Models/        ← Eloquent models
│   ├── ExternalServices/  ← HTTP clients, FCM, WhatsApp, etc.
│   ├── Jobs/              ← Queue jobs
│   ├── Persistence/
│   │   └── Repositories/  ← Eloquent repository implementations
│   └── Providers/
│       ├── {Name}ServiceProvider.php
│       ├── RepositoryServiceProvider.php  ← binds interface → implementation
│       └── RouteServiceProvider.php
│
└── Presentation/
    ├── Http/
    │   ├── Controllers/
    │   ├── Requests/
    │   └── Resources/
    ├── Resources/
    │   └── Lang/
    │       ├── ar/
    │       └── en/
    └── Routes/
        ├── api.php         ← public/shared API routes
        └── admin.php       ← admin-only routes (where applicable)
```

### Namespace Map (using Notifications as example)

| Layer | Namespace |
|-------|-----------|
| Use Cases | `App\Modules\Notifications\Application\UseCases` |
| DTOs | `App\Modules\Notifications\Application\DTOs` |
| Exceptions | `App\Modules\Notifications\Application\Exceptions` |
| Listeners | `App\Modules\Notifications\Application\Listeners` |
| Domain Events | `App\Modules\Notifications\Domain\Events` |
| Domain Enums | `App\Modules\Notifications\Domain\Enums` |
| Domain Interfaces | `App\Modules\Notifications\Domain\Interfaces` |
| Eloquent Models | `App\Modules\Notifications\Infrastructure\Database\Models` |
| Repository | `App\Modules\Notifications\Infrastructure\Persistence\Repositories` |
| Service Provider | `App\Modules\Notifications\Infrastructure\Providers` |
| Controller | `App\Modules\Notifications\Presentation\Http\Controllers` |
| Form Requests | `App\Modules\Notifications\Presentation\Http\Requests` |
| API Resources | `App\Modules\Notifications\Presentation\Http\Resources` |
| Lang | `App\Modules\Notifications\Presentation\Resources\Lang` |
| Routes | `App\Modules\Notifications\Presentation\Routes` |

---

## 4. Database Architecture

### Core Rules
- **Single database** — no multi-tenancy, no tenant scoping needed.
- **No ENUM columns** — all coded values are `TINYINT UNSIGNED`.
- **Laravel IntBackedEnum classes** handle all ENUM logic — value map documented in column `COMMENT`.
- **UUID primary keys** — domain entities use named UUID PKs (`user_id`, `order_id`, etc.), not auto-increment `id`. The `HasUuid` trait in `Core` generates UUID v4 automatically on model creation. Infrastructure/pivot tables (jobs, permissions, audit_logs) use BIGINT `id`.
- **Normalized to 3NF** — the `orders` table is split into 6 focused child tables.
- **Financial columns** — always `DECIMAL(12,2)` for amounts, `DECIMAL(10,4)` for commission rates. Never `float` or `double`.

### Table Inventory

```
Auth & Users:
  users                       ← all account types via account_type TINYINT
  password_reset_otps         ← OTP-based password reset
  personal_access_tokens      ← Sanctum (secondary; JWT is primary)

Spatie RBAC:
  permissions, roles
  model_has_permissions, model_has_roles, role_has_permissions

User Profiles (sub-tables of users):
  shipping_companies, shipping_company_addresses
  delivery_agents, agent_zones
  staff_members, departments

Locations:
  governorates, cities, addresses

Orders (3NF split):
  orders                      ← identity & assignment only
  order_customer_info         ← customer name & phones
  order_addresses             ← delivery address
  order_financials            ← all money fields
  order_items                 ← quantities & description
  order_schedules             ← expected & postponed dates
  order_approvals             ← approval flags
  order_status_history        ← immutable audit log
  order_proofs                ← delivery proof uploads

Finance:
  collections
  returns
  settlements
  approval_requests
  refusal_timer_logs

Chat:
  conversations
  conversation_participants
  messages
  message_reads

System:
  notifications
  postponed_schedules
  system_settings             ← seeded with default values
  media_files                 ← file attachments metadata
  audit_logs                  ← user action audit trail

Queue & Cache (Laravel infra):
  jobs, job_batches, failed_jobs
  cache, cache_locks
  sessions
```

### TINYINT Enum Maps

Every `TINYINT` coded column has its map in the `COMMENT` and a corresponding Laravel `IntBackedEnum`. Always read the comment before writing queries.

| Column | Table | Map |
|--------|-------|-----|
| `account_type` | users | 1=admin, 2=company, 3=agent, 4=staff |
| `commission_type` | shipping_companies, delivery_agents | 1=percentage, 2=fixed |
| `vehicle_type` | delivery_agents | 1=motorcycle, 2=car, 3=van, 4=bicycle, 5=on_foot |
| `file_type` | order_proofs | 1=image, 2=pdf, 3=other |
| `collection_type` | collections | 1=cod, 2=shipping_fee, 3=partial |
| `return_status` | returns | 1=pending, 2=received_by_admin, 3=sent_to_company |
| `settlement_type` | settlements | 1=agent, 2=company |
| `settlement_status` | settlements | 1=draft, 2=approved, 3=paid |
| `approval_type` | approval_requests | 1=price_change, 2=shipping_fee, 3=partial_amount |
| `approval_status` | approval_requests | 1=pending, 2=approved, 3=rejected, 4=expired |
| `resolution` | refusal_timer_logs | 1=delivered, 2=refused_paid, 3=refused_no_pay, 4=expired |
| `message_type` | messages | 1=text, 2=image, 3=attachment |
| `notification_type` | notifications | 1=new_order, 2=status_change, 3=approval_request, 4=timer_start, 5=timer_expired, 6=new_message, 7=phone_updated, 8=postponed_reminder |
| `data_type` | system_settings | 1=string, 2=integer, 3=decimal, 4=boolean, 5=json |

### Order Statuses (seeded, never modify manually)

| ID | Code | Has Collection | Is Terminal |
|----|------|---------------|-------------|
| 1 | pending | No | No |
| 2 | assigned | No | No |
| 3 | out_for_delivery | No | No |
| 4 | awaiting_approval | No | No |
| 5 | delivered | **Yes** | **Yes** |
| 6 | delivered_price_changed | **Yes** | **Yes** |
| 7 | partial_delivery | **Yes** | **Yes** |
| 8 | refused_paid_shipping | **Yes** | **Yes** |
| 9 | refused_no_payment | No | **Yes** |
| 10 | customer_cancelled | No | **Yes** |
| 11 | no_answer | No | No |
| 12 | phone_off | No | No |
| 13 | customer_evading | No | No |
| 14 | unsafe_area | No | No |
| 15 | postponed | No | No |
| 16 | outside_governorate | No | No |
| 17 | wrong_phone | No | No |

---

## 5. Naming Conventions

### Files & Classes

| Type | Convention | Example |
|------|-----------|---------|
| Model | PascalCase singular | `Order`, `DeliveryAgent`, `OrderFinancial` |
| Controller | PascalCase + Controller | `OrderController` |
| Use Case | Verb + Noun + UseCase | `CreateOrderUseCase`, `AssignOrderUseCase` |
| DTO | Verb + Noun + DTO | `CreateOrderDTO`, `UpdateOrderStatusDTO` |
| Repository Interface | Noun + RepositoryInterface | `OrderRepositoryInterface` |
| Repository | Noun + Repository | `OrderRepository` |
| Domain Service | Noun + DomainService | `OrderDomainService` |
| Form Request | Store/Update + Noun + Request | `StoreOrderRequest`, `UpdateOrderStatusRequest` |
| API Resource | Noun + Resource | `OrderResource`, `NotificationResource` |
| Exception | Descriptive + Exception | `OrderNotFoundException`, `TimerExpiredException` |
| Job | Verb + Noun + Job | `SendFcmNotificationJob` |
| Event | PastTense noun | `OrderAssigned`, `NewMessageSent` |
| Listener | Handle + EventName | `HandleOrderAssigned`, `HandleNewMessageSent` |
| Enum | Noun + Enum | `OrderStatusEnum`, `NotificationTypeEnum` |

### Database

| Type | Convention | Example |
|------|-----------|---------|
| Table | `snake_case` plural | `orders`, `delivery_agents`, `order_financials` |
| Domain PK | `{entity_singular}_id` UUID string | `user_id`, `order_id`, `notification_id` |
| Infra/pivot PK | `id` BIGINT UNSIGNED auto-increment | `audit_logs.id`, `roles.id` |
| Foreign Key | `{referenced_singular}_id` | `order_id`, `agent_id`, `company_id` |
| Boolean | `is_{state}` or `has_{feature}` | `is_active`, `is_settled`, `is_read` |
| Coded value | `TINYINT UNSIGNED` with COMMENT | `commission_type`, `notification_type` |
| Financial | `DECIMAL(12,2)` amounts, `DECIMAL(10,4)` rates | `collected_amount`, `commission_value` |
| GPS coords | `DECIMAL(10,7)` | `gps_lat`, `gps_lng` |
| Timestamps | `created_at`, `updated_at` | standard Laravel |
| Soft delete | `deleted_at` | on real-world entity models |

### Routes

```
/api/v1/auth/login                     → Login (public)
/api/v1/auth/forgot-password           → OTP request (public)
/api/v1/auth/reset-password            → OTP verification + password reset (public)
/api/v1/auth/me, /logout, /refresh, /change-password → JWT required
/api/v1/admin/...                      → Super admin routes
/api/v1/locations/...                  → Location lookups (mostly public)
/api/v1/notifications/...              → Notifications (auth:api)
/api/dashboard/...                     → Dashboard stats (auth:api)
```

### Variable & Method Names

- Variables: `camelCase`
- Methods: `camelCase`, verb-first: `assignOrder()`, `markAsRead()`, `sendNotification()`
- Constants: `UPPER_SNAKE_CASE`
- Config keys: `snake_case`
- Enum cases: `PascalCase` — `OrderStatusEnum::Delivered`

---

## 6. UUID Primary Keys

**All domain entity tables use UUID string PKs** with named columns, not auto-increment `id`.

```php
// The HasUuid trait (app/Modules/Core/Infrastructure/Traits/HasUuid.php)
// auto-generates a UUID v4 on the 'creating' model event.

class Notification extends Model
{
    use HasUuid;

    protected $primaryKey   = 'notification_id';
    public    $incrementing = false;
    protected $keyType      = 'string';

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

    protected $casts = [
        'notification_type' => NotificationTypeEnum::class,
        'data'              => 'array',
        'is_read'           => 'boolean',
        'sent_via_fcm'      => 'boolean',
        'read_at'           => 'datetime',
    ];
}
```

**UUID FK migrations** reference the named UUID PK column:

```php
// Correct — FK to a UUID PK column
$table->string('user_id', 36)->index();
$table->foreign('user_id', 'fk_notif_user')
      ->references('user_id')
      ->on('users')
      ->cascadeOnDelete();

// Infra/pivot tables that do NOT represent domain entities still use BIGINT:
$table->id(); // fine for audit_logs, roles, permissions, jobs, etc.
```

---

## 7. Laravel Enum Classes (IntBackedEnum)

**All TINYINT coded columns must have a corresponding IntBackedEnum in the module's `Domain/Enums/` folder.**

```php
<?php
namespace App\Modules\Orders\Domain\Enums;

enum OrderStatusEnum: int
{
    case Pending               = 1;
    case Assigned              = 2;
    case OutForDelivery        = 3;
    case AwaitingApproval      = 4;
    case Delivered             = 5;
    case DeliveredPriceChanged = 6;
    case PartialDelivery       = 7;
    case RefusedPaidShipping   = 8;
    case RefusedNoPayment      = 9;
    case CustomerCancelled     = 10;
    case NoAnswer              = 11;
    case PhoneOff              = 12;
    case CustomerEvading       = 13;
    case UnsafeArea            = 14;
    case Postponed             = 15;
    case OutsideGovernorate    = 16;
    case WrongPhone            = 17;

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Delivered,
            self::DeliveredPriceChanged,
            self::PartialDelivery,
            self::RefusedPaidShipping,
            self::RefusedNoPayment,
            self::CustomerCancelled,
        ]);
    }

    public function requiresCollection(): bool
    {
        return in_array($this, [
            self::Delivered,
            self::DeliveredPriceChanged,
            self::PartialDelivery,
            self::RefusedPaidShipping,
        ]);
    }

    public function labelAr(): string
    {
        return match($this) {
            self::Pending               => 'بانتظار التوزيع',
            self::Assigned              => 'معيّن لمندوب',
            self::OutForDelivery        => 'خرج للتوصيل',
            self::AwaitingApproval      => 'بانتظار الموافقة',
            self::Delivered             => 'تم التوصيل',
            self::DeliveredPriceChanged => 'تم التوصيل بتغيير سعر',
            self::PartialDelivery       => 'تسليم جزئي',
            self::RefusedPaidShipping   => 'رفض + دفع رسوم الشحن',
            self::RefusedNoPayment      => 'رفض وعدم دفع رسوم الشحن',
            self::CustomerCancelled     => 'ألغى العميل',
            self::NoAnswer              => 'لا يوجد رد',
            self::PhoneOff              => 'الهاتف مغلق',
            self::CustomerEvading       => 'تهرّب / مختفي',
            self::UnsafeArea            => 'منطقة غير آمنة',
            self::Postponed             => 'مؤجل',
            self::OutsideGovernorate    => 'خارج المحافظة',
            self::WrongPhone            => 'رقم هاتف خاطئ',
        };
    }
}
```

Cast every coded column in its model:

```php
protected $casts = [
    'notification_type' => NotificationTypeEnum::class,
    'message_type'      => MessageTypeEnum::class,
];
```

---

## 8. Model Rules

```php
<?php

namespace App\Modules\Notifications\Infrastructure\Database\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Core\Infrastructure\Traits\HasUuid;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\User;

class Notification extends Model
{
    use HasUuid;

    protected $table        = 'notifications';
    protected $primaryKey   = 'notification_id';
    public    $incrementing = false;
    protected $keyType      = 'string';

    protected $fillable = [
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

    protected $casts = [
        'notification_type' => NotificationTypeEnum::class,
        'data'              => 'array',
        'is_read'           => 'boolean',
        'sent_via_fcm'      => 'boolean',
        'read_at'           => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
```

### Model Rules Checklist
- [ ] Use `HasUuid` trait — set `$primaryKey`, `$incrementing = false`, `$keyType = 'string'`
- [ ] Always use `$fillable` — never `$guarded = []`
- [ ] Cast every TINYINT coded column to its IntBackedEnum
- [ ] Cast all timestamps to `'datetime'`
- [ ] Cast financial columns: `'decimal:2'`
- [ ] Cast boolean TINYINT columns to `'boolean'`
- [ ] Cast JSON columns to `'array'`
- [ ] Use `softDeletes()` on models representing real-world entities
- [ ] Place model in `Infrastructure/Database/Models/` inside its module only
- [ ] BelongsTo FK and referenced PK must both be specified explicitly (UUID named PKs)

---

## 9. Controller Rules

Controllers are **thin wrappers** only. Zero business logic inside.

```php
<?php

namespace App\Modules\Notifications\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Application\Helpers\ApiResponse;
use App\Modules\Notifications\Application\UseCases\GetUserNotificationsUseCase;
use App\Modules\Notifications\Application\UseCases\MarkNotificationReadUseCase;
use App\Modules\Notifications\Application\UseCases\GetUnreadCountUseCase;
use App\Modules\Notifications\Presentation\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private GetUserNotificationsUseCase $getNotifications,
        private MarkNotificationReadUseCase $markRead,
        private GetUnreadCountUseCase       $getUnreadCount,
    ) {}

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $notifications = $this->getNotifications->execute(auth()->id());
        return ApiResponse::paginated(NotificationResource::collection($notifications));
    }

    public function markRead(string $id): \Illuminate\Http\JsonResponse
    {
        $this->markRead->execute($id, auth()->id());
        return ApiResponse::success(null, __('notifications.messages.marked_read'));
    }

    public function unreadCount(): \Illuminate\Http\JsonResponse
    {
        $count = $this->getUnreadCount->execute(auth()->id());
        return ApiResponse::success(['count' => $count]);
    }
}
```

---

## 10. Use Case Rules

```php
<?php

namespace App\Modules\Notifications\Application\UseCases;

use App\Modules\Notifications\Application\DTOs\SendNotificationDTO;
use App\Modules\Notifications\Domain\Interfaces\NotificationRepositoryInterface;
use App\Modules\Notifications\Infrastructure\Jobs\SendFcmNotificationJob;

class SendNotificationUseCase
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
    ) {}

    public function execute(SendNotificationDTO $dto): void
    {
        $notification = $this->repository->create([
            'user_id'           => $dto->userId,
            'notification_type' => $dto->type->value,
            'title_ar'          => $dto->titleAr,
            'body_ar'           => $dto->bodyAr,
            'data'              => $dto->data,
            'sent_via_fcm'      => (bool) $dto->fcmToken,
        ]);

        if ($dto->fcmToken) {
            SendFcmNotificationJob::dispatch(
                fcmToken:         $dto->fcmToken,
                notificationType: $dto->type,
                titleAr:          $dto->titleAr,
                bodyAr:           $dto->bodyAr,
                data:             $dto->data,
            )->onQueue('notifications');
        }
    }
}
```

### Use Case Rules Checklist
- [ ] Inject all dependencies via constructor — never `new SomeClass()`
- [ ] Wrap multi-step DB operations in `DB::transaction()`
- [ ] Throw typed exceptions on business rule violations
- [ ] Never access `Request` — receive plain array or DTO from controller
- [ ] Never return Eloquent Models — return array or DTO
- [ ] Never couple to Laravel-specific classes (`Collection`, `Response`, etc.)
- [ ] Never put validation logic here — that belongs in FormRequest

---

## 11. DTO Rules

```php
<?php

namespace App\Modules\Notifications\Application\DTOs;

use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;

readonly class SendNotificationDTO
{
    public function __construct(
        public string               $userId,
        public NotificationTypeEnum $type,
        public string               $titleAr,
        public string               $bodyAr,
        public array                $data        = [],
        public ?string              $fcmToken    = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId:   $data['user_id'],
            type:     $data['type'],
            titleAr:  $data['title_ar'],
            bodyAr:   $data['body_ar'],
            data:     $data['data'] ?? [],
            fcmToken: $data['fcm_token'] ?? null,
        );
    }
}
```

---

## 12. API Response Structure

**All responses must use this exact structure — no raw `json()` calls directly in controllers.**

```php
<?php
// app/Modules/Core/Application/Helpers/ApiResponse.php

namespace App\Modules\Core\Application\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    public static function paginated(mixed $resource, string $message = ''): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => $message ?: __('messages.success'),
            'data'    => $resource->collection,
            'meta'    => [
                'current_page' => $resource->resource->currentPage(),
                'last_page'    => $resource->resource->lastPage(),
                'per_page'     => $resource->resource->perPage(),
                'total'        => $resource->resource->total(),
                'has_more'     => $resource->resource->hasMorePages(),
            ],
        ]);
    }
}
```

**Success:**
```json
{ "status": true, "message": "تم إنشاء الطلب", "data": { ... } }
```

**Error:**
```json
{ "status": false, "message": "بيانات غير صحيحة", "errors": { "phone": ["مطلوب"] } }
```

**Paginated:**
```json
{ "status": true, "message": "Success", "data": [...], "meta": { "current_page": 1, "last_page": 5, "total": 73 } }
```

---

## 13. Migration Rules

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            // UUID primary key — named after the entity
            $table->string('notification_id', 36)->primary();

            // UUID foreign key — references the named UUID PK of users
            $table->string('user_id', 36)->index('idx_notif_user');
            $table->foreign('user_id', 'fk_notif_user')
                  ->references('user_id')
                  ->on('users')
                  ->cascadeOnDelete();

            $table->unsignedTinyInteger('notification_type')
                  ->comment('1=new_order,2=status_change,3=approval_request,4=timer_start,5=timer_expired,6=new_message,7=phone_updated,8=postponed_reminder')
                  ->index('idx_notif_type');

            $table->string('title_ar');
            $table->text('body_ar');
            $table->json('data')->nullable();
            $table->tinyInteger('is_read')->default(0);
            $table->timestamp('read_at')->nullable();
            $table->tinyInteger('sent_via_fcm')->default(0);
            $table->string('fcm_message_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read'], 'idx_notif_user_read');
            $table->index(['user_id', 'created_at'], 'idx_notif_user_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
```

### Migration Rules Checklist
- [ ] Domain entity PK: `$table->string('{entity}_id', 36)->primary()` — UUID string, NOT `$table->id()`
- [ ] Infra/pivot PK: `$table->id()` — BIGINT, acceptable for non-domain tables
- [ ] UUID FK: `$table->string('{entity}_id', 36)` + explicit `->foreign()` referencing named PK column
- [ ] Always name FK constraints (`'fk_table_column'`) and indexes (`'idx_table_column'`)
- [ ] Financial amounts: `decimal(12,2)` — never float or double
- [ ] Commission/rate values: `decimal(10,4)`
- [ ] GPS coordinates: `decimal(10,7)`
- [ ] Coded columns: `unsignedTinyInteger` with `COMMENT` showing the int→meaning map
- [ ] Index every FK column and every column used in WHERE / ORDER BY
- [ ] Add `unique()` on 1:1 child tables (e.g. `order_financials.order_id`)
- [ ] Never use `ENUM` — always `TINYINT UNSIGNED`

---

## 14. Resource Rules

```php
<?php

namespace App\Modules\Notifications\Presentation\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->notification_id,
            'type'              => [
                'code'  => $this->notification_type->value,
                'label' => $this->notification_type->name,
            ],
            'title'             => $this->title_ar,
            'body'              => $this->body_ar,
            'data'              => $this->data,
            'is_read'           => $this->is_read,
            'read_at'           => $this->read_at?->toISOString(),
            'created_at'        => $this->created_at?->toISOString(),
        ];
    }
}
```

### Resource Rules Checklist
- [ ] Always expose the UUID PK as `id` in the JSON output (map `user_id` → `id`, `notification_id` → `id`)
- [ ] Always use `$this->whenLoaded('relation')` on relationships — never unconditional eager loads
- [ ] Use `$this->when(condition, fn() => ...)` for optional fields
- [ ] Format timestamps as ISO string or `diffForHumans()` — never raw Carbon
- [ ] Expose the Enum label alongside its integer value
- [ ] Never expose readable role strings from raw integers — use Enum `name` or `labelAr()`
- [ ] Never expose `password`, `fcm_token`, or `remember_token`

---

## 15. Form Request Rules

```php
<?php

namespace App\Modules\Orders\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // fine-grained check done in Use Case
    }

    public function rules(): array
    {
        $validStatuses = implode(',', array_column(OrderStatusEnum::cases(), 'value'));

        return [
            'status_id'        => ['required', 'integer', "in:{$validStatuses}"],
            'gps_lat'          => ['required_if:status_id,5,8,9,14', 'numeric', 'between:-90,90'],
            'gps_lng'          => ['required_if:status_id,5,8,9,14', 'numeric', 'between:-180,180'],
            'postponed_date'   => ['required_if:status_id,15', 'date', 'after:today'],
            'collected_amount' => ['required_if:status_id,5,6,7,8', 'numeric', 'min:0'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ];
    }
}
```

---

## 16. Business Logic — Critical Rules

### Financial Calculation

```php
// Commission calculation — ALWAYS computed server-side, never trust client
$commissionAmount = match($agent->commission_type) {
    CommissionTypeEnum::Percentage => $collectedAmount * ($agent->commission_value / 100),
    CommissionTypeEnum::Fixed      => $agent->commission_value,
};

$netDue = $collectedAmount - $commissionAmount;
```

### GPS Rules
- **No live tracking** — GPS is captured only at specific events
- Events that require GPS: `delivery_confirmation`, `refusal_start/end/check`, `unsafe_area`, `no_answer`
- Store in `gps_checkpoints` table with `event_type` TINYINT (migration not yet created)
- During refusal timer: validate agent stays within `system_settings.refusal_gps_radius_meters` (default 200m)

### Refusal Timer Rules
- Duration: read from `system_settings` key `refusal_timer_minutes` (default 5)
- On timer start: record GPS, set `expires_at`, dispatch `RefusalTimerExpiredJob` via queue
- During timer: agent must NOT move beyond radius — log `agent_left_zone = 1` if violated
- On timer expiry with no resolution → status becomes `refused_no_payment` (ID=9)

### Order 3NF Rules
- When creating an order, **always** create all 6 child rows in the same DB transaction:
  `order_customer_info`, `order_addresses`, `order_financials`, `order_items`, `order_schedules`, `order_approvals`
- When loading an order for the API, eager load all 6 child relations
- Never flatten child data back into `orders` table

### Settlement Rules
- `settlement_type=1` (agent): `reference_entity_id` = `delivery_agents.agent_id`
- `settlement_type=2` (company): `reference_entity_id` = `shipping_companies.company_id`
- After settlement is `paid` (status=3): update linked `collections.settlement_id`
- Update `delivery_agents.balance` or `shipping_companies.balance` after settlement

---

## 17. Notification Rules

### FCM Push (Mobile Agent App)

FCM is implemented via a custom `FcmService` using `Illuminate\Support\Facades\Http` with the legacy FCM HTTP endpoint. There is **no Composer Firebase/FCM package** — the `FCM_SERVER_KEY` env variable holds the server key.

```php
// Always dispatch to queue — never send synchronously
SendFcmNotificationJob::dispatch(
    fcmToken:         $agent->fcm_token,
    notificationType: NotificationTypeEnum::NewOrder,
    titleAr:          'طلب جديد',
    bodyAr:           "تم تعيين طلب #{$order->internal_code} لك",
    data:             ['order_id' => $order->order_id],
)->onQueue('notifications');
```

The `SendNotificationUseCase` handles both DB persistence and FCM dispatch together — use it as the single entry point for all notification sending.

### Notification Events

| Event | Recipient | Type |
|-------|-----------|------|
| Order assigned | Agent | `new_order` (1) |
| Status changed | Company | `status_change` (2) |
| Price change requested | Company | `approval_request` (3) |
| Refusal timer started | Company | `timer_start` (4) |
| Refusal timer expired | Company + Agent | `timer_expired` (5) |
| New message | Recipient | `new_message` (6) |
| Phone number updated | Agent | `phone_updated` (7) |
| Postponed reminder | Agent | `postponed_reminder` (8) |

---

## 18. Chat System Rules

Chat tables (`conversations`, `conversation_participants`, `messages`, `message_reads`) are **migrated** but the Chat module business logic is not yet built.

```php
// When building the Chat module:

// Creating a conversation linked to an order
$conversation = Conversation::create([
    'order_id' => $order->order_id,
    'subject'  => "طلب #{$order->internal_code}",
]);

// Adding participants
ConversationParticipant::insert([
    ['conversation_id' => $conversation->conversation_id, 'user_id' => $adminUserId],
    ['conversation_id' => $conversation->conversation_id, 'user_id' => $agentUserId],
]);

// Creating a message
Message::create([
    'conversation_id' => $conversation->conversation_id,
    'sender_id'       => auth()->id(),
    'message_type'    => MessageTypeEnum::Text->value,
    'body'            => $messageText,
]);

// Broadcast via Firebase Realtime/WebSocket after creating
broadcast(new NewMessageEvent($message))->toOthers();
```

- Realtime via Firebase — never polling
- Update `conversation_participants.last_read_at` when user opens the conversation
- File uploads stored locally via `media_files` table — store path + original name

---

## 19. Queue Rules

```php
// Queues used in this project
const QUEUES = [
    'default',       // general async work          — 60s timeout
    'notifications', // FCM, in-app notifications   — 120s timeout
    'reports',       // report generation           — 300s timeout
    'timers',        // refusal timer expiry jobs   — 60s timeout
    'chat',          // broadcast events            — 30s timeout
];

// All jobs must:
// 1. implement ShouldQueue
// 2. use the correct queue name
// 3. define $tries and $timeout

class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        private string               $fcmToken,
        private NotificationTypeEnum $notificationType,
        private string               $titleAr,
        private string               $bodyAr,
        private array                $data = [],
    ) {}

    public function handle(FcmService $fcmService): void
    {
        $fcmService->send(
            token:   $this->fcmToken,
            title:   $this->titleAr,
            body:    $this->bodyAr,
            data:    array_merge($this->data, ['type' => $this->notificationType->value]),
        );
    }
}
```

---

## 20. What Claude Must Never Do

- ❌ Never use `ENUM` columns in migrations — always `TINYINT UNSIGNED`
- ❌ Never hardcode order status integers in business logic — always use `OrderStatusEnum::Delivered->value`
- ❌ Never use `$table->id()` for domain entity PKs — domain tables use `$table->string('{entity}_id', 36)->primary()`
- ❌ Never use `foreignId()` to reference a UUID PK — always use `$table->string('{entity}_id', 36)` + explicit `->foreign()`
- ❌ Never query without loading child relations when returning order data — always eager load all 6 child tables
- ❌ Never put Eloquent queries in Use Cases — Use Cases call Repositories via interface
- ❌ Never put logic in controllers — controllers call Use Cases only
- ❌ Never use `$request->validate()` inline — always use a Form Request class
- ❌ Never return raw Eloquent models from Use Cases — return array or DTO
- ❌ Never return raw `$model->toArray()` in controllers — always use a Resource
- ❌ Never use `float` or `double` for monetary columns — always `decimal(12,2)`
- ❌ Never send FCM notifications synchronously — always dispatch to `notifications` queue
- ❌ Never do live GPS tracking — GPS only at specific documented events
- ❌ Never skip `DB::transaction()` when creating an order (must create all 6 child rows atomically)
- ❌ Never calculate commission client-side — always compute server-side
- ❌ Never expose `password`, `fcm_token`, or `remember_token` in API responses
- ❌ Never instantiate Use Cases or Repositories with `new` — always inject via constructor
- ❌ Never skip `->whenLoaded()` on Resource relationships — always use conditional loading
- ❌ Never write a migration without adding named indexes on FK columns and WHERE-clause columns
- ❌ Never skip writing the TINYINT map in the column `COMMENT`
- ❌ Never bypass the `ApiResponse` helper — never return `response()->json()` directly
- ❌ Never use unscoped queries like `Model::all()` — always filter by relevant user/entity IDs
- ❌ Never create a Use Case that depends on `Request` — dependency on HTTP layer is forbidden
- ❌ Never put business rules in the Domain layer that depend on Laravel facades
- ❌ Never add a Composer FCM package — FCM is custom HTTP via `FcmService`

---

## 21. Current Build Status

| Module | Status | Notes |
|--------|--------|-------|
| Core | ✅ Built | `HasUuid`, `ApiResponse`, `ModuleServiceProvider`, `MediaFile` |
| Auth | ✅ Built | JWT login/logout/refresh, OTP password reset, WhatsApp welcome |
| Users | ✅ Built | All account types (admin, company, agent, staff), import/export |
| Roles | ✅ Built | Spatie permissions wrapper |
| Locations | ✅ Built | Governorates, cities, addresses |
| Departments | ✅ Built | Staff department management |
| AuditLog | ✅ Built | Action audit trail |
| Settings | ✅ Built | Key-value system settings |
| Notifications | ✅ Built | FCM push + in-app, all 8 event types |
| Dashboard | ✅ Partial | Stats/charts; holds Order model temporarily |
| Orders | 🔧 Stub | DB tables migrated, routes empty, models not in module yet |
| Collections | ❌ Not built | DB table exists |
| Returns | ❌ Not built | DB table exists |
| Settlements | ❌ Not built | DB table exists |
| ApprovalRequests | ❌ Not built | DB table exists |
| RefusalTimer | ❌ Not built | DB table exists |
| Chat | ❌ Not built | DB tables exist (conversations, messages, message_reads) |
| GpsCheckpoints | ❌ Not built | DB migration not yet created |
| PostponedSchedules | ❌ Not built | DB table exists |

---

## 22. Module File Reference

| Module | Location | Key Enums |
|--------|----------|-----------|
| Core | `app/Modules/Core/` | — |
| Auth | `app/Modules/Auth/` | — |
| Users | `app/Modules/Users/` | `AccountTypeEnum`, `CommissionTypeEnum`, `VehicleTypeEnum` |
| Roles | `app/Modules/Roles/` | — |
| Locations | `app/Modules/Locations/` | — |
| Departments | `app/Modules/Departments/` | — |
| AuditLog | `app/Modules/AuditLog/` | — |
| Settings | `app/Modules/Settings/` | `SettingDataTypeEnum` |
| Notifications | `app/Modules/Notifications/` | `NotificationTypeEnum` |
| Dashboard | `app/Modules/Dashboard/` | `OrderStatusEnum` (temporary home for Order model) |
| Orders | `app/Modules/Orders/` | `OrderStatusEnum`, `ApprovalTypeEnum`, `ApprovalStatusEnum` |

---

*Last updated: June 2026 — Mersal v1.0 — Egyptian B2B Logistics Platform*
