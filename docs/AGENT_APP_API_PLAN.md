# Mersal — Agent Mobile App: API Implementation Plan

> **Scope:** All REST API endpoints required to power the Flutter mobile app for delivery agents (`account_type = 3`, role: `delivery_agent`).
> **Reference prototype:** https://marsal-core-platform.lovable.app/agent
> **Backend:** Laravel 13, JWT auth (`auth:api` guard), modular architecture.
> **Last reviewed:** June 2026

---

## Table of Contents

1. [Screen → API Map](#1-screen--api-map)
2. [Already-Built APIs (reuse as-is)](#2-already-built-apis-reuse-as-is)
3. [Phase 1 — Core Order Flow](#3-phase-1--core-order-flow)
4. [Phase 2 — Collections & Finance](#4-phase-2--collections--finance)
5. [Phase 3 — Schedule & Postponed](#5-phase-3--schedule--postponed)
6. [Phase 4 — Refusal Timer](#6-phase-4--refusal-timer)
7. [Phase 5 — Agent Profile & Settings](#7-phase-5--agent-profile--settings)
8. [Route Summary Table](#8-route-summary-table)
9. [Implementation Notes](#9-implementation-notes)
10. [Presentation Layer Standards](#10-presentation-layer-standards)

---

## 1. Screen → API Map

| App Screen | Route | APIs Needed | Phase |
|------------|-------|-------------|-------|
| Home Dashboard | `/agent` | Agent dashboard stats, upcoming orders | Phase 1 |
| My Orders List | `/agent/orders` | List agent orders (filtered) | Phase 1 |
| Order Detail | `/agent/orders/:id` | Get order detail, update status | Phase 1 |
| Delivery Confirmation | (action on detail) | Upload proof photo, submit collection | Phase 1 |
| Postponed Schedule — List | `/agent/schedule` | List postponed orders | Phase 3 |
| Postponed Schedule — Calendar | `/agent/schedule` toggle | Calendar view with dot indicators | Phase 3 |
| My Collections | `/agent/collections` | List & summary of agent's COD | Phase 2 |
| Refusal Timer | (triggered from detail) | Start timer, resolve | Phase 4 |
| Profile | `/agent/profile` | Agent profile, vehicle, financial info | Phase 5 |
| Notifications | `/agent/notifications` | **Already built** ✅ | — |
| Login | (not in prototype) | **Already built** ✅ | — |
| Change Password | (profile settings) | **Already built** ✅ | — |

---

## 2. Already-Built APIs (reuse as-is)

These endpoints exist and require no changes for the agent app — the JWT guard returns the correct data scoped to the authenticated user.

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/auth/login` | Agent login — returns JWT |
| `POST` | `/api/v1/auth/logout` | Invalidate token |
| `POST` | `/api/v1/auth/refresh` | Refresh JWT |
| `GET` | `/api/v1/auth/me` | Get current user identity |
| `POST` | `/api/v1/auth/change-password` | Change password |
| `POST` | `/api/v1/auth/forgot-password` | OTP request |
| `POST` | `/api/v1/auth/reset-password` | OTP verify + reset |
| `GET` | `/api/v1/notifications` | List paginated notifications |
| `GET` | `/api/v1/notifications/unread-count` | Badge count |
| `PATCH` | `/api/v1/notifications/{id}/read` | Mark one read |
| `PATCH` | `/api/v1/notifications/read-all` | Mark all read |
| `DELETE` | `/api/v1/notifications/read` | Delete read notifications |
| `GET` | `/api/v1/locations/governorates` | Governorate list |
| `GET` | `/api/v1/locations/cities/{governorateId}` | Cities by governorate |

---

## 3. Phase 1 — Core Order Flow

**Module:** `Orders`
**Middleware:** `auth:api` + `role:delivery_agent`
**Controllers:** `AgentDashboardController`, `AgentOrderController` — both use `ApiResponseTrait` (`$this->success()` / `$this->error()`), never `ApiResponse::` directly.

| Endpoint | Controller method | Resource |
|----------|-------------------|----------|
| `GET /dashboard` | `AgentDashboardController@index` | `AgentDashboardResource` (+ nested `AgentUpcomingOrderResource`) |
| `GET /orders` | `AgentOrderController@index` | `AgentOrderListResource` |
| `GET /orders/{orderId}` | `AgentOrderController@show` | `AgentOrderDetailResource` |
| `PATCH /orders/{orderId}/status` | `AgentOrderController@updateStatus` | `AgentOrderStatusUpdatedResource` |
| `POST /orders/{orderId}/proof` | `AgentOrderController@uploadProof` | `AgentOrderProofResource` |

This is the highest-priority phase. The order detail + status update flow is the primary daily workflow for every delivery agent.

---

### 3.1 Agent Dashboard

```
GET /api/v1/agent/dashboard
```

**Purpose:** Powers the Home screen — greeting stats, today's summary, upcoming orders preview, performance score.

**Response:**
```json
{
  "status": true,
  "data": {
    "agent": {
      "name": "خالد العتيبي",
      "fcm_token_registered": true
    },
    "today": {
      "orders_count": 4,
      "collected_amount": 460.00,
      "delivered_count": 1,
      "pending_count": 3
    },
    "performance": {
      "delivery_rate_percent": 96,
      "week_label": "هذا الأسبوع"
    },
    "upcoming_orders": [
      {
        "order_id": "uuid",
        "internal_code": "MRS-10241",
        "status_id": 2,
        "status_label": "تم التعيين",
        "customer_name": "سارة الحربي",
        "city": "مدينة نصر",
        "governorate": "القاهرة",
        "cod_amount": 850.00
      }
    ]
  }
}
```

**Business rules:**
- `upcoming_orders`: only statuses `assigned (2)`, `out_for_delivery (3)`, `awaiting_approval (4)` — max 5 records, ordered by `expected_delivery_date ASC`
- `collected_amount`: sum of today's `collections` linked to this agent
- `delivery_rate_percent`: `(delivered / total_terminal) * 100` for the last 7 days
- Only orders assigned to `auth()->id()` agent

---

### 3.2 List Agent Orders

```
GET /api/v1/agent/orders
```

**Purpose:** Powers the "طلباتي" (My Orders) list screen with search and filter tabs.

**Query Parameters:**

| Param | Type | Values | Default |
|-------|------|--------|---------|
| `status` | string | `new`, `in_delivery`, `postponed`, `all` | `all` |
| `search` | string | Order code, customer name, or phone | — |
| `page` | int | — | 1 |
| `per_page` | int | — | 20 |

**Status filter mapping:**
- `new` → `status_id IN (1, 2)` — pending or assigned
- `in_delivery` → `status_id = 3`
- `postponed` → `status_id = 15`
- `all` → all non-terminal statuses (IDs: 1–4, 11–12, 15)

**Response:**
```json
{
  "status": true,
  "data": [
    {
      "order_id": "uuid",
      "internal_code": "MRS-10241",
      "status": { "id": 2, "label": "تم التعيين", "color": "blue" },
      "customer_name": "سارة الحربي",
      "customer_phone": "01055512024",
      "city": "مدينة نصر",
      "governorate": "القاهرة",
      "cod_amount": 850.00,
      "expected_delivery_date": "2026-06-20"
    }
  ],
  "meta": { "current_page": 1, "last_page": 3, "per_page": 20, "total": 47 }
}
```

**Business rules:**
- Scope to `orders.agent_id = auth()->id()` — never return other agents' orders
- Exclude terminal statuses (5, 6, 7, 8, 9, 10) from the default `all` filter
- Search: `LIKE` on `order_customer_info.customer_name`, `order_customer_info.phone_1`, `orders.internal_code`

---

### 3.3 Get Order Detail

```
GET /api/v1/agent/orders/{orderId}
```

**Purpose:** Powers the Order Detail screen — full order info, customer data, address, financial breakdown, status history, action buttons.

**Response:**
```json
{
  "status": true,
  "data": {
    "order_id": "uuid",
    "internal_code": "MRS-10241",
    "company_name": "شحن سريع",
    "status": { "id": 2, "label": "تم التعيين" },
    "customer": {
      "name": "سارة الحربي",
      "phone_1": "01055512024",
      "phone_2": "01099912345"
    },
    "address": {
      "governorate": "القاهرة",
      "city": "مدينة نصر",
      "street": "شارع عباس العقاد",
      "building": "12",
      "floor": "3",
      "apartment": "5",
      "landmark": "أمام البنك الأهلي",
      "notes": ""
    },
    "financials": {
      "cod_amount": 850.00,
      "shipping_fee": 35.00,
      "extra_fees": 0.00,
      "discount": 0.00,
      "total_due": 850.00
    },
    "items": {
      "quantity": 2,
      "description": "ملابس",
      "weight_kg": 1.5
    },
    "schedule": {
      "expected_delivery_date": "2026-06-20",
      "postponed_date": null
    },
    "approvals": {
      "requires_otp": false,
      "requires_photo": true
    },
    "status_history": [
      {
        "status_id": 1,
        "status_label": "بانتظار التوزيع",
        "changed_at": "2026-06-18T10:00:00Z",
        "changed_by": "الإدارة"
      },
      {
        "status_id": 2,
        "status_label": "معيّن لمندوب",
        "changed_at": "2026-06-19T08:30:00Z",
        "changed_by": "الإدارة"
      }
    ],
    "proof_photos": [],
    "available_actions": ["start_delivery", "postpone", "call_customer"]
  }
}
```

**Business rules:**
- Authorize: `order.agent_id = auth()->id()` — abort 403 otherwise
- Eager load all 6 child tables: `orderCustomerInfo`, `orderAddress`, `orderFinancials`, `orderItems`, `orderSchedule`, `orderApprovals`
- `available_actions` is computed server-side based on current `status_id`:
  - `assigned (2)` → `[start_delivery, postpone, call_customer]`
  - `out_for_delivery (3)` → `[confirm_delivery, refuse, no_answer, phone_off, postpone, call_customer]`
  - `awaiting_approval (4)` → `[call_customer]` (waiting for admin)
  - Terminal statuses → `[]`

---

### 3.4 Update Order Status

```
PATCH /api/v1/agent/orders/{orderId}/status
```

**Purpose:** The core action button — Start Delivery, Confirm Delivery, Refuse, No Answer, etc.

**Request Body:**
```json
{
  "status_id": 3,
  "notes": ""
}
```

**For delivery confirmation (status_id = 5, 6, 7, 8), additional fields:**
```json
{
  "status_id": 5,
  "collected_amount": 850.00,
  "collection_type": 1,
  "notes": "تم التسليم بنجاح"
}
```

**For postpone (status_id = 15):**
```json
{
  "status_id": 15,
  "postponed_date": "2026-06-22",
  "notes": "العميل طلب التأجيل"
}
```

**For price-change delivery (status_id = 6):**
```json
{
  "status_id": 6,
  "new_cod_amount": 720.00,
  "collected_amount": 720.00,
  "collection_type": 1
}
```

**Business rules:**
- Authorize: `order.agent_id = auth()->id()`
- Validate transition is allowed from current status (use `OrderStatusEnum`)
- `status_id IN (5,6,7,8)` → require `collected_amount`, create `collections` record, update `agent.balance`
- `status_id = 15` → require `postponed_date` (must be in the future), write to `order_schedules.postponed_date`, create `postponed_schedules` record
- `status_id = 6` → require `new_cod_amount`, create `approval_requests` record (type=1), set order status to `awaiting_approval (4)` until admin approves (this triggers a notification to admin and company)
- Always insert a row in `order_status_history`
- Always fire `SendNotificationUseCase` to notify the shipping company of status change
- Wrap everything in `DB::transaction()`

**Response:**
```json
{
  "status": true,
  "message": "تم تحديث حالة الطلب",
  "data": {
    "order_id": "uuid",
    "new_status": { "id": 3, "label": "خرج للتوصيل" },
    "collection_created": false
  }
}
```

---

### 3.5 Upload Delivery Proof Photo

```
POST /api/v1/agent/orders/{orderId}/proof
```

**Purpose:** Upload photo evidence of delivery (required for terminal statuses when `order_approvals.requires_photo = true`).

**Request:** `multipart/form-data`

| Field | Type | Required |
|-------|------|----------|
| `photo` | file (jpg/png/pdf) | Yes |
| `file_type` | int (1=image, 2=pdf, 3=other) | Yes |

**Business rules:**
- Authorize: `order.agent_id = auth()->id()`
- Store via `media_files` table — save to `storage/app/proofs/{agent_id}/{order_id}/`
- Create `order_proofs` record with `file_type` TINYINT
- Maximum file size: read from `system_settings` key `max_proof_size_mb`

**Response:**
```json
{
  "status": true,
  "message": "تم رفع صورة الإثبات",
  "data": {
    "proof_id": "uuid",
    "file_url": "/storage/proofs/agent-uuid/order-uuid/photo.jpg",
    "file_type": 1
  }
}
```

---

## 4. Phase 2 — Collections & Finance

**Module:** `Collections` (new module to build)
**Middleware:** `auth:api` + `role:delivery_agent`

---

### 4.1 List Agent Collections

```
GET /api/v1/agent/collections
```

**Purpose:** Powers the "تحصيلاتي" screen — every COD amount the agent has collected and not yet handed over.

**Query Parameters:**

| Param | Type | Values | Default |
|-------|------|--------|---------|
| `settled` | bool | `true` / `false` | `false` |
| `page` | int | — | 1 |

**Response:**
```json
{
  "status": true,
  "data": [
    {
      "collection_id": "uuid",
      "order_id": "uuid",
      "internal_code": "MRS-10245",
      "customer_name": "خالد منصور",
      "collection_type": { "id": 1, "label": "مبلغ الاستلام (COD)" },
      "collected_amount": 460.00,
      "is_settled": false,
      "settlement_id": null,
      "collected_at": "2026-06-20T09:15:00Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 3 }
}
```

---

### 4.2 Agent Collections Summary

```
GET /api/v1/agent/collections/summary
```

**Purpose:** Header stats on the collections screen — total held amount, count, breakdown by type.

**Response:**
```json
{
  "status": true,
  "data": {
    "total_unsettled": 1250.00,
    "unsettled_count": 3,
    "breakdown": {
      "cod": 1100.00,
      "shipping_fee": 100.00,
      "partial": 50.00
    },
    "last_settlement_date": "2026-06-15",
    "agent_balance": 1250.00
  }
}
```

**Business rules:**
- Scope to `collections.agent_id = auth()->id()`
- `total_unsettled`: sum of `collected_amount` where `settlement_id IS NULL`
- `agent_balance`: read from `delivery_agents.balance` — should match `total_unsettled`

---

## 5. Phase 3 — Schedule & Postponed

**Module:** `Orders` (extend with schedule-specific endpoints)
**Middleware:** `auth:api` + `role:delivery_agent`

---

### 5.1 List Postponed Orders

```
GET /api/v1/agent/schedule
```

**Purpose:** Powers the "الطلبات المؤجلة" list view — all orders with `status_id = 15` assigned to this agent.

**Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `date` | date (Y-m-d) | Filter by specific postponed date |
| `month` | string (Y-m) | Filter by month (for calendar view data) |

**Response:**
```json
{
  "status": true,
  "data": [
    {
      "order_id": "uuid",
      "internal_code": "MRS-10244",
      "customer_name": "أحمد فتحي",
      "city": "مصر الجديدة",
      "cod_amount": 1100.00,
      "postponed_date": "2026-06-06",
      "postpone_notes": "العميل طلب التأجيل"
    }
  ],
  "meta": { "total": 1 }
}
```

---

### 5.2 Calendar View Data

```
GET /api/v1/agent/schedule/calendar
```

**Purpose:** Powers the calendar toggle in the schedule screen — returns which dates in a given month have postponed orders and how many.

**Query Parameters:**

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `month` | string (Y-m) | Yes | e.g. `2026-06` |

**Response:**
```json
{
  "status": true,
  "data": {
    "month": "2026-06",
    "total_postponed": 5,
    "dates": {
      "2026-06-06": 1,
      "2026-06-08": 1,
      "2026-06-16": 1,
      "2026-06-19": 1,
      "2026-06-22": 1
    }
  }
}
```

**Business rules:**
- Scope to `orders.agent_id = auth()->id()` with `status_id = 15`
- Join `order_schedules` on `postponed_date`
- Group by `DATE(order_schedules.postponed_date)` and count

---

### 5.3 Reschedule Postponed Order

```
PATCH /api/v1/agent/orders/{orderId}/reschedule
```

**Purpose:** Change the postponed date for an order already in postponed status.

**Request Body:**
```json
{
  "postponed_date": "2026-06-25",
  "notes": "العميل سيكون متاحاً بعد أسبوع"
}
```

**Business rules:**
- Order must have `status_id = 15` (Postponed)
- `postponed_date` must be in the future
- Update `order_schedules.postponed_date`
- Insert new row in `order_status_history` (same status, new timestamp + note)
- Update `postponed_schedules` record

---

## 6. Phase 4 — Refusal Timer

**Module:** `RefusalTimer` (new module — `refusal_timer_logs` table already exists)
**Middleware:** `auth:api` + `role:delivery_agent`

This is a critical business flow. When a customer refuses delivery, a timer starts and the agent must wait the configured duration (default: 5 minutes, from `system_settings`) before resolving the outcome.

---

### 6.1 Start Refusal Timer

```
POST /api/v1/agent/orders/{orderId}/refusal/start
```

**Purpose:** Called when the agent selects "رفض" on an order that is `out_for_delivery (3)`.

**Request Body:** _(empty — no required fields)_

**Response:**
```json
{
  "status": true,
  "data": {
    "timer_id": "uuid",
    "started_at": "2026-06-20T11:00:00Z",
    "expires_at": "2026-06-20T11:05:00Z",
    "duration_seconds": 300
  }
}
```

**Business rules:**
- Order must be `out_for_delivery (3)` and `agent_id = auth()->id()`
- Read timer duration from `system_settings` key `refusal_timer_minutes` (default 5)
- Insert `refusal_timer_logs` row with `expires_at`
- Dispatch `RefusalTimerExpiredJob` to queue `timers` with delay = timer duration
- Send `timer_start (4)` notification to shipping company

---

### 6.2 Resolve Refusal Timer

```
POST /api/v1/agent/orders/{orderId}/refusal/resolve
```

**Purpose:** Called when the timer completes or when the agent manually resolves (customer agreed, or timer expired).

**Request Body:**
```json
{
  "resolution": 2,
  "collected_amount": 35.00,
  "collection_type": 2,
  "notes": "دفع رسوم الشحن فقط"
}
```

**Resolution codes (from `refusal_timer_logs.resolution` TINYINT):**

| Value | Meaning | Order Status After |
|-------|---------|-------------------|
| `1` | `delivered` — customer agreed during timer | `delivered (5)` |
| `2` | `refused_paid` — customer paid shipping fee | `refused_paid_shipping (8)` |
| `3` | `refused_no_pay` — customer refused to pay anything | `refused_no_payment (9)` |
| `4` | `expired` — timer ran out with no resolution (set by job) | `refused_no_payment (9)` |

**Business rules:**
- Resolution `2` requires `collected_amount + collection_type` → creates `collections` record
- Resolution `1` (delivered) follows full delivery confirmation flow — requires `collected_amount`
- Update `refusal_timer_logs.resolution`, `resolved_at`
- Update order status accordingly
- Insert `order_status_history` row
- Send `timer_expired (5)` notification to company and agent

---

### 6.3 Get Active Timer Status

```
GET /api/v1/agent/orders/{orderId}/refusal/active
```

**Purpose:** App re-opens and needs to resume an in-progress refusal timer (e.g. app was backgrounded).

**Response:**
```json
{
  "status": true,
  "data": {
    "timer_id": "uuid",
    "started_at": "2026-06-20T11:00:00Z",
    "expires_at": "2026-06-20T11:05:00Z",
    "seconds_remaining": 145
  }
}
```

Returns `null` in `data` if no active timer exists for this order.

---

## 7. Phase 5 — Agent Profile & Settings

**Module:** `Users` (extend existing module)
**Middleware:** `auth:api` + `role:delivery_agent`

---

### 7.1 Get Agent Profile

```
GET /api/v1/agent/profile
```

**Purpose:** Powers the "حسابي" (My Account) screen — all personal, vehicle, and financial info.

**Response:**
```json
{
  "status": true,
  "data": {
    "user_id": "uuid",
    "name": "خالد العتيبي",
    "phone": "+20 100 555 12 24",
    "avatar_initials": "خ",
    "agent": {
      "agent_id": "uuid",
      "vehicle_type": { "id": 1, "label": "دراجة نارية" },
      "vehicle_plate": "ABC 1234",
      "national_id": "29900112345678",
      "commission_type": { "id": 1, "label": "نسبة مئوية" },
      "commission_value": 8.0,
      "balance": 1250.00,
      "is_active": true
    },
    "stats": {
      "total_delivered": 342,
      "average_rating": 4.9,
      "active_since": "2025-01-15"
    },
    "zones": [
      { "zone_id": "uuid", "city": "القاهرة", "governorate": "القاهرة" }
    ]
  }
}
```

---

### 7.2 Update FCM Token

```
PATCH /api/v1/agent/fcm-token
```

**Purpose:** Called on app launch and on token refresh to keep push notifications working.

**Request Body:**
```json
{
  "fcm_token": "fcm_token_string_here"
}
```

**Business rules:**
- Update `users.fcm_token` for `auth()->id()`
- No response body needed beyond success confirmation

---

## 8. Route Summary Table

All agent-facing routes use prefix `/api/v1/agent/` and middleware `auth:api` + `role:delivery_agent`.

| # | Method | Endpoint | Use Case Class | Phase |
|---|--------|----------|----------------|-------|
| 1 | `GET` | `/api/v1/agent/dashboard` | `GetAgentDashboardUseCase` | 1 |
| 2 | `GET` | `/api/v1/agent/orders` | `ListAgentOrdersUseCase` | 1 |
| 3 | `GET` | `/api/v1/agent/orders/{orderId}` | `GetOrderDetailUseCase` | 1 |
| 4 | `PATCH` | `/api/v1/agent/orders/{orderId}/status` | `UpdateOrderStatusUseCase` | 1 |
| 5 | `POST` | `/api/v1/agent/orders/{orderId}/proof` | `UploadDeliveryProofUseCase` | 1 |
| 6 | `GET` | `/api/v1/agent/collections` | `ListAgentCollectionsUseCase` | 2 |
| 7 | `GET` | `/api/v1/agent/collections/summary` | `GetAgentCollectionsSummaryUseCase` | 2 |
| 8 | `GET` | `/api/v1/agent/schedule` | `ListPostponedOrdersUseCase` | 3 |
| 9 | `GET` | `/api/v1/agent/schedule/calendar` | `GetScheduleCalendarUseCase` | 3 |
| 10 | `PATCH` | `/api/v1/agent/orders/{orderId}/reschedule` | `RescheduleOrderUseCase` | 3 |
| 11 | `POST` | `/api/v1/agent/orders/{orderId}/refusal/start` | `StartRefusalTimerUseCase` | 4 |
| 12 | `POST` | `/api/v1/agent/orders/{orderId}/refusal/resolve` | `ResolveRefusalTimerUseCase` | 4 |
| 13 | `GET` | `/api/v1/agent/orders/{orderId}/refusal/active` | `GetActiveTimerUseCase` | 4 |
| 14 | `GET` | `/api/v1/agent/profile` | `GetAgentProfileUseCase` | 5 |
| 15 | `PATCH` | `/api/v1/agent/fcm-token` | `UpdateFcmTokenUseCase` | 5 |

**Already Built (no changes needed):**
- Auth endpoints (login, logout, refresh, me, change-password, OTP)
- Notifications (index, unread-count, mark-read, read-all, delete-read)
- Locations (governorates, cities)

---

## 9. Implementation Notes

### New Modules to Create

| Module | Tables It Owns | Priority |
|--------|---------------|----------|
| `Collections` | `collections` | Phase 2 |
| `RefusalTimer` | `refusal_timer_logs` | Phase 4 |

> The `Orders` module owns `order_status_history`, `order_proofs`, and all `order_*` child tables. Extend it in phases 1 and 3.

---

### Middleware Strategy

Add a custom `EnsureIsAgent` middleware or use Spatie's `role:delivery_agent` check consistently. All Phase 1–5 routes are agent-only.

```php
// app/Modules/Orders/Presentation/Routes/api.php
Route::prefix('api/v1/agent')
    ->middleware(['auth:api', 'role:delivery_agent'])
    ->group(function () {
        Route::get('dashboard', [AgentDashboardController::class, 'index']);
        Route::get('orders', [AgentOrderController::class, 'index']);
        Route::get('orders/{orderId}', [AgentOrderController::class, 'show']);
        Route::patch('orders/{orderId}/status', [AgentOrderController::class, 'updateStatus']);
        Route::post('orders/{orderId}/proof', [AgentOrderController::class, 'uploadProof']);
        Route::patch('orders/{orderId}/reschedule', [AgentOrderController::class, 'reschedule']);
        Route::get('schedule', [AgentScheduleController::class, 'index']);
        Route::get('schedule/calendar', [AgentScheduleController::class, 'calendar']);
        // ... collections, refusal timer, profile
    });
```

---

### Notification Triggers (per action)

| Agent Action | Notification Sent To | Type |
|---|---|---|
| Status updated | Shipping Company | `status_change (2)` |
| Price change requested | Shipping Company | `approval_request (3)` |
| Refusal timer started | Shipping Company | `timer_start (4)` |
| Refusal timer resolved/expired | Company + Agent | `timer_expired (5)` |

All notifications go through `SendNotificationUseCase` — never fire FCM directly from a controller.

---

### Implementation Sequence (Recommended)

```
Week 1:  Phase 1 — Orders (endpoints 1–5)
         → OrderStatusEnum transitions
         → UpdateOrderStatusUseCase with transaction
         → Status notifications

Week 2:  Phase 2 — Collections (endpoints 6–7)
         → Collections module scaffold
         → Link to Phase 1 status update (auto-create collection on delivery)

Week 3:  Phase 3 — Schedule (endpoints 8–10)
         → Lightweight extension of Orders module
         → PostponedSchedules model + calendar aggregation

Week 4:  Phase 4 — Refusal Timer (endpoints 11–13)
         → RefusalTimer module scaffold
         → RefusalTimerExpiredJob

Week 5:  Phase 5 — Profile (endpoints 14–15)
         → Extend Users module
         → FCM token update on app launch
```

---

## 10. Presentation Layer Standards

All agent API endpoints (Phases 1–5) must follow the same presentation conventions used elsewhere in Mersal (e.g. `AdminUserController`, `NotificationController`).

### Controllers

- Use `ApiResponseTrait` on every agent controller — call `$this->success()`, `$this->error()`, or `$this->paginatedWithData()` as needed.
- Do **not** import or call `ApiResponse::success()` / `ApiResponse::error()` directly in controllers.
- Controllers stay thin: validate via Form Request → call Use Case → wrap result in a Json Resource → return via trait.

```php
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;

class AgentOrderController extends Controller
{
    use ApiResponseTrait;

    public function show(Request $request, string $orderId): JsonResponse
    {
        $order = $this->getOrderDetail->execute($request->user()->user_id, $orderId);

        return $this->success(
            new AgentOrderDetailResource($order),
            __('orders::messages.order_detail_success'),
        );
    }
}
```

### Json Resources (mandatory for all responses)

- Every successful endpoint must return data through a dedicated `JsonResource` class under `Presentation/Http/Resources/`.
- Never return raw arrays, Eloquent models, or `$model->toArray()` from controllers.
- Use Cases return domain data (models, DTOs, or structured arrays); **Resources** own the API shape (field names, nesting, ISO timestamps, enum labels).
- Expose the UUID primary key as `id` in JSON (plus the named key e.g. `order_id` where mobile clients expect it).
- Use `$this->whenLoaded()` for optional relationships; use `::collection()` for lists.
- Paginated lists: merge `Resource::collection($items)` with `PaginationMeta::getMeta($paginator)` inside `$this->success([...])`.

### Phase 1 Resource inventory

| Resource | Wraps |
|----------|--------|
| `AgentDashboardResource` | Dashboard aggregate array (stats + upcoming order models) |
| `AgentUpcomingOrderResource` | Single `Order` model (dashboard preview row) |
| `AgentOrderListResource` | Single `Order` model (list row) |
| `AgentOrderDetailResource` | Single `Order` model with all child relations eager-loaded |
| `AgentOrderStatusUpdatedResource` | Status-update result array from Use Case |
| `AgentOrderProofResource` | Proof upload result array from Use Case |

### Phases 2–5 (when built)

Apply the same rules: one Resource per response shape, `ApiResponseTrait` in every new agent controller (`AgentCollectionsController`, `AgentScheduleController`, `AgentRefusalController`, `AgentProfileController`, etc.).

---

*Generated: June 2026 — Mersal v1.0 — Agent Mobile App API Plan*
