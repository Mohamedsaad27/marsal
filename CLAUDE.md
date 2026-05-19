# ShipOps — Claude AI Rules (Master File)

> **Read this file completely before starting any task.**
> Marsal is a B2B logistics and delivery management system built on Laravel + Flutter.
> Every rule here is mandatory. No exceptions unless explicitly stated per-task.
> The system connects shipping companies ↔ admin ↔ delivery agents.

---

## 1. Project Identity

| Key | Value |
|-----|-------|
| Product | ShipOps |
| Market | Egyptian B2B logistics market |
| Backend | Laravel 13 — REST API |
| FrontEnd Dashboard Admin | React |
| Mobile App | Flutter (Android + iOS) |
| Architecture | Modular Monolith — Module-based Clean Architecture |
| Auth | JWT (`tymon/jwt-auth`) — guards: `api` |
| Authorization | Role-Based Access Control |
| Primary Keys | `UUIDs ` no auto-increment|
| Language | Arabic (primary UI) + English (code & comments) - Always Add Readable Human Messages | 
| Realtime | Laravel + Firbase (chat + notifications) |
| Push | Firebase Cloud Messaging (FCM) |
| Storage | Locale Storage (proof photos, attachments) |
| Database | MySQL 8.0.30+ — single database, no multi-tenancy |

---

## 2. The Three System Users — Never Confuse Them

| Role ID | Role Code | Who They Are | Interface |
|---------|-----------|--------------|-----------|
| 1 | `super_admin` | Internal admin team | Web Dashboard | Full Access 
| 2 | `shipping_company` | Client companies that send orders | Web Portal |
| 3 | `delivery_agent` | Field delivery personnel | Flutter Mobile App |

### Business Flow (Read Before Every Task)

```
Shipping Company → sends orders
        ↓
Super Admin → receives, reviews, assigns to agents
        ↓
Delivery Agent → attempts delivery → updates status
        ↓
Agent collects cash (when applicable)
        ↓
Admin receives collections from agent
        ↓
Admin settles (transfers net amount) to shipping company
```

---

## 3. Directory Structure

```
app/
├── Modules/
│   ├── Core/                    ← Shared base classes, traits, helpers
│   │   ├── Application/
│   │   │   └── Helpers/
│   │   │       ├── ApiResponse.php
│   │   │       └── PaginationMeta.php
│   │   ├── Infrastructure/
│   │   │   └── Traits/
│   │   │       └── HasFormattedDates.php
│   │   └── Presentation/
│   │       └── Resources/
│   │           └── lang/
│   │               ├── ar/messages.php
│   │               └── en/messages.php
│   │
│   ├── Auth/                    ← Login, logout, token refresh
│   ├── Users/                   ← User management (all 3 roles)
│   ├── ShippingCompanies/       ← Company profiles & addresses
│   ├── DeliveryAgents/          ← Agent profiles & zones
│   ├── Orders/                  ← Core order lifecycle
│   │   └── Domain/
│   │       └── Enums/
│   │           ├── OrderStatusEnum.php
│   │           ├── CollectionTypeEnum.php
│   │           ├── ApprovalTypeEnum.php
│   │           └── ApprovalStatusEnum.php
│   ├── Collections/             ← Cash collection records
│   ├── Returns/                 ← Return goods tracking
│   ├── Settlements/             ← Financial clearance
│   ├── ApprovalRequests/        ← Price/fee change approvals
│   ├── RefusalTimer/            ← 5-min refusal timer logic
│   ├── Chat/                    ← Conversations & messages
│   ├── Notifications/           ← FCM push + in-app notifications
│   ├── GpsCheckpoints/          ← Spot GPS capture (NOT live tracking)
│   ├── PostponedSchedules/      ← Agent postponement calendar
│   └── SystemSettings/          ← Runtime config key-value store

database/
└── migrations/
    └── 2024_01_01_XXXXXX_create_{table}_table.php
```

Each module follows this **Clean Architecture** internal structure:

```
Modules/Orders/
│
├── Application/
│   ├── DTOs/
│   │   ├── CreateOrderDTO.php
│   │   └── UpdateOrderDTO.php
│   ├── Exceptions/
│   │   └── OrderNotFoundException.php
│   └── UseCases/
│       ├── CreateOrderUseCase.php
│       ├── AssignOrderUseCase.php
│       ├── UpdateOrderStatusUseCase.php
│       └── GetOrdersUseCase.php
│
├── Domain/
│   ├── Enums/
│   │   ├── OrderStatusEnum.php
│   │   └── CollectionTypeEnum.php
│   ├── Interfaces/
│   │   └── OrderRepositoryInterface.php
│   └── Services/
│       └── OrderDomainService.php
│
├── Infrastructure/
│   ├── Database/
│   │   ├── Migrations/
│   │   ├── Seeders/
│   │   └── Models/
│   │       ├── Order.php
│   │       ├── OrderCustomerInfo.php
│   │       ├── OrderAddress.php
│   │       ├── OrderFinancial.php
│   │       ├── OrderItem.php
│   │       ├── OrderSchedule.php
│   │       └── OrderApproval.php
│   ├── Persistence/
│   │   └── OrderRepository.php
│   └── Providers/
│       └── OrderServiceProvider.php
│
└── Presentation/
    ├── Http/
    │   ├── Controllers/
    │   │   └── OrderController.php
    │   ├── Requests/
    │   │   ├── StoreOrderRequest.php
    │   │   └── UpdateOrderStatusRequest.php
    │   └── Resources/
    │       └── OrderResource.php
    ├── Resources/
    │   └── lang/
    │       ├── ar/orders.php
    │       └── en/orders.php
    └── Routes/
        └── api.php
```

### Namespace Map

| Layer | Namespace |
|-------|-----------|
| Use Cases | `App\Modules\Orders\Application\UseCases` |
| DTOs | `App\Modules\Orders\Application\DTOs` |
| Exceptions | `App\Modules\Orders\Application\Exceptions` |
| Domain Enums | `App\Modules\Orders\Domain\Enums` |
| Domain Interfaces | `App\Modules\Orders\Domain\Interfaces` |
| Domain Services | `App\Modules\Orders\Domain\Services` |
| Eloquent Models | `App\Modules\Orders\Infrastructure\Database\Models` |
| Repository | `App\Modules\Orders\Infrastructure\Persistence` |
| Service Provider | `App\Modules\Orders\Infrastructure\Providers` |
| Controller | `App\Modules\Orders\Presentation\Http\Controllers` |
| Form Requests | `App\Modules\Orders\Presentation\Http\Requests` |
| API Resources | `App\Modules\Orders\Presentation\Http\Resources` |
| Lang | `App\Modules\Orders\Presentation\Resources\lang\` |
| Routes | `App\Modules\Orders\Presentation\Routes` |

---

## 4. Database Architecture

### Core Rules
- **Single database** — no multi-tenancy, no tenant scoping needed.
- **No ENUM columns** — all coded values are `TINYINT UNSIGNED`.
- **Laravel IntBackedEnum classes** handle all ENUM logic — value map documented in column `COMMENT`.
- **Normalized to 3NF** — the `orders` table is split into 7 focused child tables.
- **Financial columns** — always `DECIMAL(15,2)` for amounts, `DECIMAL(10,4)` for commission rates. Never `float` or `double`.

### Table Inventory (29 tables)

```
Auth & Users:
  roles, users, personal_access_tokens

Companies & Agents:
  shipping_companies, shipping_company_addresses
  delivery_agents, agent_zones

Orders (3NF split):
  order_statuses (seeded — 17 statuses)
  orders                  ← identity & assignment only
  order_customer_info     ← customer name & phones
  order_addresses         ← delivery address
  order_financials        ← all money fields
  order_items             ← quantities
  order_schedules         ← expected & postponed dates
  order_approvals         ← approval flags
  order_status_history    ← immutable audit log
  order_proofs            ← delivery proof uploads

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

System:
  notifications
  gps_checkpoints
  postponed_schedules
  system_settings         ← seeded with 9 default values
```

### TINYINT Enum Maps

Every `TINYINT` coded column has its map in the `COMMENT` and a corresponding Laravel `IntBackedEnum`. Always read the comment before writing queries.

| Column | Table | Map |
|--------|-------|-----|
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
| `event_type` | gps_checkpoints | 1=delivery_confirmation, 2=refusal_start, 3=refusal_check, 4=refusal_end, 5=unsafe_area, 6=no_answer |
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
| Service | Noun + DomainService | `OrderDomainService` |
| Form Request | Store/Update + Noun + Request | `StoreOrderRequest`, `UpdateOrderStatusRequest` |
| API Resource | Noun + Resource | `OrderResource`, `AgentResource` |
| Exception | Descriptive + Exception | `OrderNotFoundException`, `TimerExpiredException` |
| Job | Verb + Noun + Job | `SendRefusalTimerNotificationJob` |
| Event | PastTense | `OrderAssigned`, `CollectionRecorded` |
| Enum | Noun + Enum | `OrderStatusEnum`, `CollectionTypeEnum` |

### Database

| Type | Convention | Example |
|------|-----------|---------|
| Table | `snake_case` plural | `orders`, `delivery_agents`, `order_financials` |
| Primary Key | `id` BIGINT UNSIGNED | `id` |
| Foreign Key | `{referenced_singular}_id` | `order_id`, `agent_id`, `company_id` |
| Boolean | `is_{state}` or `has_{feature}` | `is_active`, `is_settled`, `is_available` |
| Coded value | `TINYINT UNSIGNED` | `commission_type`, `return_status` |
| Financial | `DECIMAL(12,2)` amounts, `DECIMAL(10,4)` rates | `collected_amount`, `commission_value` |
| GPS coords | `DECIMAL(10,7)` | `gps_lat`, `gps_lng` |
| Timestamp columns | `created_at`, `updated_at` | standard Laravel |
| Soft delete | `deleted_at` | optional per model |

### Routes

```
/api/v1/auth/...           → Login, logout, token refresh (no auth needed)
/api/v1/admin/...          → Super admin routes
/api/v1/company/...        → Shipping company routes
/api/v1/agent/...          → Delivery agent mobile routes
```

### Variable & Method Names

- Variables: `camelCase`
- Methods: `camelCase`, verb-first: `assignOrder()`, `recordCollection()`, `startRefusalTimer()`
- Constants: `UPPER_SNAKE_CASE`
- Config keys: `snake_case`
- Enum cases: `PascalCase` — `OrderStatusEnum::Delivered`

---

## 6. Laravel Enum Classes (IntBackedEnum)

**All TINYINT coded columns must have a corresponding IntBackedEnum in the module's `Domain/Enums/` folder.**

```php
<?php
// app/Modules/Orders/Domain/Enums/OrderStatusEnum.php

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
            self::PhoneOff             => 'الهاتف مغلق',
            self::CustomerEvading      => 'تهرّب / مختفي',
            self::UnsafeArea           => 'منطقة غير آمنة',
            self::Postponed            => 'مؤجل',
            self::OutsideGovernorate   => 'خارج المحافظة',
            self::WrongPhone           => 'رقم هاتف خاطئ',
        };
    }
}
```

Cast every coded column in its model:

```php
protected $casts = [
    'status_id'      => OrderStatusEnum::class,
    'return_status'  => ReturnStatusEnum::class,
    'message_type'   => MessageTypeEnum::class,
];
```

---

## 7. Model Rules

```php
<?php

namespace App\Modules\Orders\Infrastructure\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'reference_no',
        'internal_code',
        'shipping_company_id',
        'delivery_agent_id',
        'status_id',
        'assigned_at',
        'delivered_at',
    ];

    protected $casts = [
        'status_id'   => OrderStatusEnum::class,
        'assigned_at' => 'datetime',
        'delivered_at'=> 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    // ── 3NF Child relationships ─────────────────────────────
    public function customerInfo(): HasOne
    {
        return $this->hasOne(OrderCustomerInfo::class, 'order_id');
    }

    public function address(): HasOne
    {
        return $this->hasOne(OrderAddress::class, 'order_id');
    }

    public function financials(): HasOne
    {
        return $this->hasOne(OrderFinancial::class, 'order_id');
    }

    public function items(): HasOne
    {
        return $this->hasOne(OrderItem::class, 'order_id');
    }

    public function schedule(): HasOne
    {
        return $this->hasOne(OrderSchedule::class, 'order_id');
    }

    public function approval(): HasOne
    {
        return $this->hasOne(OrderApproval::class, 'order_id');
    }
}
```

### Model Rules Checklist
- [ ] Always use `$fillable` — never `$guarded = []`
- [ ] Cast every TINYINT coded column to its IntBackedEnum
- [ ] Cast all timestamps to `'datetime'`
- [ ] Cast financial columns: `'decimal:2'`
- [ ] Use `softDeletes()` on models representing real-world entities
- [ ] Place model in `Infrastructure/Database/Models/` inside its module only

---

## 8. Controller Rules

Controllers are **thin wrappers** only. Zero business logic inside.

```php
<?php

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Application\Helpers\ApiResponse;
use App\Modules\Orders\Application\UseCases\CreateOrderUseCase;
use App\Modules\Orders\Application\UseCases\AssignOrderUseCase;
use App\Modules\Orders\Application\UseCases\GetOrdersUseCase;
use App\Modules\Orders\Presentation\Http\Requests\StoreOrderRequest;
use App\Modules\Orders\Presentation\Http\Requests\AssignOrderRequest;
use App\Modules\Orders\Presentation\Http\Resources\OrderResource;

class OrderController extends Controller
{
    public function __construct(
        private CreateOrderUseCase $createOrder,
        private AssignOrderUseCase $assignOrder,
        private GetOrdersUseCase   $getOrders,
    ) {}

    public function index(): \Illuminate\Http\JsonResponse
    {
        $orders = $this->getOrders->execute();

        return ApiResponse::paginated(OrderResource::collection($orders));
    }

    public function store(StoreOrderRequest $request): \Illuminate\Http\JsonResponse
    {
        $order = $this->createOrder->execute($request->validated());

        return ApiResponse::success(
            new OrderResource($order),
            __('orders.created'),
            201
        );
    }

    public function assign(AssignOrderRequest $request, int $id): \Illuminate\Http\JsonResponse
    {
        $order = $this->assignOrder->execute($id, $request->validated());

        return ApiResponse::success(new OrderResource($order), __('orders.assigned'));
    }
}
```

---

## 9. Use Case Rules

```php
<?php

namespace App\Modules\Orders\Application\UseCases;

use App\Modules\Orders\Application\DTOs\CreateOrderDTO;
use App\Modules\Orders\Domain\Interfaces\OrderRepositoryInterface;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;
use App\Modules\Notifications\Application\UseCases\SendNotificationUseCase;
use Illuminate\Support\Facades\DB;

class CreateOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface  $repository,
        private SendNotificationUseCase   $sendNotification,
    ) {}

    public function execute(array $data): array
    {
        $dto = CreateOrderDTO::fromArray($data);

        $order = DB::transaction(function () use ($dto) {
            $order = $this->repository->create($dto);
            // create child rows (financials, customer info, address, items, schedule)
            $this->repository->createChildRecords($order->id, $dto);
            return $order;
        });

        return $order->load([
            'customerInfo', 'address', 'financials', 'items', 'schedule'
        ])->toArray();
    }
}
```

### Use Case Rules Checklist
- [ ] Inject all dependencies via constructor — never `new SomeClass()`
- [ ] Wrap multi-step DB operations in `DB::transaction()`
- [ ] Throw typed exceptions on business rule violations
- [ ] Never access `Request` — receive plain array from controller
- [ ] Never return Eloquent Models — return array or DTO
- [ ] Never couple to Laravel-specific classes (`Collection`, `Response`, etc.)
- [ ] Never put validation logic here — that belongs in FormRequest

---

## 10. DTO Rules

```php
<?php

namespace App\Modules\Orders\Application\DTOs;

readonly class CreateOrderDTO
{
    public function __construct(
        public int     $shipping_company_id,
        public string  $reference_no,
        public string  $internal_code,
        public string  $customer_name,
        public string  $customer_phone,
        public ?string $phone_alt,
        public string  $address_line,
        public ?string $governorate,
        public ?string $city,
        public ?string $area,
        public float   $original_amount,
        public int     $total_quantity,
        public ?string $item_description    = null,
        public ?string $expected_delivery_date = null,
        public ?string $address_notes       = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }
}
```

---

## 11. API Response Structure

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

## 12. Migration Rules

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_financials', function (Blueprint $table) {
            $table->id();                                          // BIGINT UNSIGNED AUTO_INCREMENT
            $table->foreignId('order_id')
                  ->unique()
                  ->constrained('orders', 'id', 'fk_of_order')
                  ->cascadeOnDelete();

            $table->decimal('original_amount', 12, 2)->default(0);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('collected_amount', 12, 2)->nullable();
            $table->decimal('shipping_fee', 12, 2)->nullable();
            $table->decimal('commission_amount', 12, 2)->nullable();
            $table->decimal('net_due_company', 12, 2)->nullable();
            $table->tinyInteger('is_settled')->default(0)->index('idx_of_settled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_financials');
    }
};
```

### Migration Rules Checklist
- [ ] PK: always `$table->id()` — BIGINT UNSIGNED
- [ ] FK: use named constraints `constrained('table', 'id', 'fk_name')`
- [ ] Financial amounts: `decimal(12,2)` — never float or double
- [ ] Commission/rate values: `decimal(10,4)`
- [ ] GPS coordinates: `decimal(10,7)`
- [ ] Coded columns: `unsignedTinyInteger` with `COMMENT` showing the int→meaning map
- [ ] Index every FK column and every column used in WHERE / ORDER BY
- [ ] Add `unique()` on 1:1 child tables (e.g. `order_financials.order_id`)
- [ ] Never use `ENUM` — always `TINYINT UNSIGNED`

---

## 13. Resource Rules

```php
<?php

namespace App\Modules\Orders\Presentation\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'reference_no'  => $this->reference_no,
            'internal_code' => $this->internal_code,
            'status'        => [
                'code'    => $this->status_id->value,
                'label'   => $this->status_id->labelAr(),
                'terminal'=> $this->status_id->isTerminal(),
            ],
            'company'       => [
                'id'   => $this->shipping_company_id,
                'name' => $this->shippingCompany?->company_name,
            ],
            'agent'         => $this->when(
                $this->delivery_agent_id,
                fn() => [
                    'id'    => $this->delivery_agent_id,
                    'name'  => $this->deliveryAgent?->user?->name,
                    'phone' => $this->deliveryAgent?->user?->phone,
                ]
            ),
            'customer'      => new OrderCustomerInfoResource($this->whenLoaded('customerInfo')),
            'address'       => new OrderAddressResource($this->whenLoaded('address')),
            'financials'    => new OrderFinancialResource($this->whenLoaded('financials')),
            'items'         => new OrderItemResource($this->whenLoaded('items')),
            'schedule'      => new OrderScheduleResource($this->whenLoaded('schedule')),
            'assigned_at'   => $this->assigned_at?->toISOString(),
            'delivered_at'  => $this->delivered_at?->toISOString(),
            'created_at'    => $this->created_at?->toISOString(),
        ];
    }
}
```

### Resource Rules Checklist
- [ ] Always expose primary key as `id` — never as `order_id`
- [ ] Always use `$this->whenLoaded('relation')` on relationships — never unconditional eager loads
- [ ] Use `$this->when(condition, fn() => ...)` for optional fields
- [ ] Format timestamps as ISO string or `diffForHumans()` — never raw
- [ ] Expose the Enum label alongside its integer value
- [ ] Never expose raw `role_id` integers — expose readable role name
- [ ] Never expose `password`, `password_hash`, `fcm_token`, or `remember_token`

---

## 14. Form Request Rules

```php
<?php

namespace App\Modules\Orders\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Orders\Domain\Enums\OrderStatusEnum;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Agents can only update their own assigned orders
        return true; // fine-grained check done in Use Case
    }

    public function rules(): array
    {
        $validStatuses = implode(',', array_column(OrderStatusEnum::cases(), 'value'));

        return [
            'status_id'         => ['required', 'integer', "in:{$validStatuses}"],
            'gps_lat'           => ['required_if:status_id,5,8,9,14', 'numeric', 'between:-90,90'],
            'gps_lng'           => ['required_if:status_id,5,8,9,14', 'numeric', 'between:-180,180'],
            'postponed_date'    => ['required_if:status_id,15', 'date', 'after:today'],
            'collected_amount'  => ['required_if:status_id,5,6,7,8', 'numeric', 'min:0'],
            'notes'             => ['nullable', 'string', 'max:500'],
        ];
    }
}
```

---

## 15. Business Logic — Critical Rules

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
- Store in `gps_checkpoints` table with `event_type` TINYINT
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
- `settlement_type=1` (agent): `reference_entity_id` = `delivery_agents.id`
- `settlement_type=2` (company): `reference_entity_id` = `shipping_companies.id`
- After settlement is `paid` (status=3): update linked `collections.settlement_id`
- Update `delivery_agents.balance` or `shipping_companies.balance` after settlement

---

## 16. Notification Rules

### FCM Push (Mobile Agent App)

```php
// Always dispatch to queue — never send synchronously
SendFcmNotificationJob::dispatch(
    fcmToken:         $agent->fcm_token,
    notificationType: NotificationTypeEnum::NewOrder,
    titleAr:          'طلب جديد',
    bodyAr:           "تم تعيين طلب #{$order->internal_code} لك",
    data:             ['order_id' => $order->id],
)->onQueue('notifications');

// Always log to notifications table
Notification::create([
    'user_id'           => $agent->user_id,
    'notification_type' => NotificationTypeEnum::NewOrder->value,
    'title_ar'          => 'طلب جديد',
    'body_ar'           => "تم تعيين طلب #{$order->internal_code} لك",
    'data'              => json_encode(['order_id' => $order->id]),
    'sent_via_fcm'      => 1,
]);
```

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

## 17. Chat System Rules

```php
// Creating a conversation linked to an order
$conversation = Conversation::create([
    'order_id' => $order->id,
    'subject'  => "طلب #{$order->internal_code}",
]);

// Adding participants
ConversationParticipant::insert([
    ['conversation_id' => $conversation->id, 'user_id' => $adminUserId],
    ['conversation_id' => $conversation->id, 'user_id' => $agentUserId],
]);

// Creating a message
Message::create([
    'conversation_id' => $conversation->id,
    'sender_id'       => auth()->id(),
    'message_type'    => MessageTypeEnum::Text->value,
    'body'            => $messageText,
]);

// Broadcast via WebSocket after creating
broadcast(new NewMessageEvent($message))->toOthers();
```

- Realtime via Laravel WebSockets or Pusher — never polling
- File uploads stored in S3/R2 — store URL in `file_url`, original name in `file_name`
- Update `conversation_participants.last_read_at` when user opens the conversation

---

## 18. Queue Rules

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

class RefusalTimerExpiredJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        private int $orderId,
        private int $timerLogId,
    ) {}

    public function handle(): void
    {
        // check if already resolved — idempotent
        // auto-close order to refused_no_payment
        // notify all parties
    }
}
```

---

## 20. What Claude Must Never Do

- ❌ Never use `ENUM` columns in migrations — always `TINYINT UNSIGNED`
- ❌ Never hardcode order status integers in business logic — always use `OrderStatusEnum::Delivered->value`
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
- ❌ Never expose `password_hash`, `fcm_token`, or `remember_token` in API responses
- ❌ Never instantiate Use Cases or Repositories with `new` — always inject via constructor
- ❌ Never skip `->whenLoaded()` on Resource relationships — always use conditional loading
- ❌ Never use `auto_increment` UUIDs — this project uses BIGINT auto-increment
- ❌ Never write a migration without adding indexes on FK columns and WHERE-clause columns
- ❌ Never skip writing the TINYINT map in the column `COMMENT`
- ❌ Never bypass the `ApiResponse` helper — never return `response()->json()` directly
- ❌ Never use `Contact::all()` or any un-scoped query — always filter by relevant IDs
- ❌ Never create a Use Case that depends on `Request` — dependency on HTTP layer is forbidden
- ❌ Never put business rules in the Domain layer that depend on Laravel facades

---

## 21. Module Build Order (Phased)

| Phase | Modules | Priority |
|-------|---------|----------|
| **P0 — Foundation** | Core (ApiResponse, base traits), Auth (JWT login), Users, Roles | Week 1 |
| **P1 — Parties** | ShippingCompanies, DeliveryAgents, agent_zones | Week 2 |
| **P2 — Orders** | Orders (all 7 tables), order_status_history, order_proofs | Weeks 3–4 |
| **P3 — Finance** | Collections, Returns, Settlements, ApprovalRequests | Weeks 5–6 |
| **P4 — Refusal Timer** | RefusalTimer (timer logic, GPS validation, queue job) | Week 7 |
| **P5 — Chat** | Conversations, Messages, WebSocket broadcast | Week 8 |
| **P6 — Notifications** | Notifications (FCM, in-app, all 8 event types) | Week 9 |
| **P7 — GPS & Schedule** | GpsCheckpoints, PostponedSchedules, reminder cron | Week 10 |
| **P8 — Reports & Polish** | Reports, SystemSettings UI, indexes, caching, API docs | Week 11+ |

---

## 22. Module File Reference

| Module | Location | Key Enum |
|--------|----------|----------|
| Auth | `app/Modules/Auth/` | — |
| Users | `app/Modules/Users/` | `RoleEnum` |
| ShippingCompanies | `app/Modules/ShippingCompanies/` | `CommissionTypeEnum` |
| DeliveryAgents | `app/Modules/DeliveryAgents/` | `VehicleTypeEnum`, `CommissionTypeEnum` |
| Orders | `app/Modules/Orders/` | `OrderStatusEnum`, `ApprovalTypeEnum`, `ApprovalStatusEnum` |
| Collections | `app/Modules/Collections/` | `CollectionTypeEnum` |
| Returns | `app/Modules/Returns/` | `ReturnStatusEnum` |
| Settlements | `app/Modules/Settlements/` | `SettlementTypeEnum`, `SettlementStatusEnum` |
| ApprovalRequests | `app/Modules/ApprovalRequests/` | `ApprovalTypeEnum`, `ApprovalStatusEnum` |
| RefusalTimer | `app/Modules/RefusalTimer/` | `RefusalResolutionEnum` |
| Chat | `app/Modules/Chat/` | `MessageTypeEnum` |
| Notifications | `app/Modules/Notifications/` | `NotificationTypeEnum` |
| GpsCheckpoints | `app/Modules/GpsCheckpoints/` | `GpsEventTypeEnum` |
| PostponedSchedules | `app/Modules/PostponedSchedules/` | — |
| SystemSettings | `app/Modules/SystemSettings/` | `SettingDataTypeEnum` |

---

*Last updated: May 2026 — ShipOps v1.0 — Egyptian B2B Logistics Platform*