# Audit Log — Frontend Value Mapping Reference

Reference for mapping `metadata`, `old_values`, and `new_values` returned by the audit log API.

**Endpoints**

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/v1/admin/audit-logs` | Paginated list + KPIs |
| `GET` | `/api/v1/admin/audit-logs/{auditable_type}/{auditable_id}` | Logs for a specific entity |

---

## API Response Item Shape

Each log entry (`AuditLogResource`) has this structure:

```json
{
  "id": 42,
  "event": { "code": 8, "label": "تم التعيين" },
  "actor_type": 1,
  "actor_type_label": "مدير نظام",
  "actor": {
    "id": "uuid",
    "name": "أحمد",
    "phone": "01000000000"
  },
  "auditable_type": "orders",
  "auditable_id": "uuid",
  "description": "تم تعيين الطلب «ORD-001» للمندوب «محمد»",
  "old_values": {},
  "new_values": {},
  "metadata": {},
  "ip_address": "192.168.1.1",
  "created_at": "2026-06-15T10:30:00.000000Z"
}
```

| Field | Type | Notes |
|-------|------|-------|
| `id` | `number` | BIGINT auto-increment |
| `event.code` | `number` | See [Event codes](#event-codes) |
| `event.label` | `string` | Arabic label (server-generated) |
| `actor_type` | `number` | See [Actor type codes](#actor-type-codes) |
| `actor_type_label` | `string` | Arabic label |
| `actor` | `object \| omitted` | Present when actor user exists |
| `auditable_type` | `string` | DB table name (e.g. `users`, `orders`) |
| `auditable_id` | `string` | UUID of the affected entity |
| `description` | `string` | Human-readable Arabic summary (prefer for display) |
| `old_values` | `object \| null` | State before the action |
| `new_values` | `object \| null` | State after the action |
| `metadata` | `object \| null` | Extra context not fitting old/new diff |
| `ip_address` | `string \| null` | Request IP when recorded |
| `created_at` | `string` | ISO 8601 timestamp |

> **Display tip:** Use `description` as the primary text. Use `metadata` / `old_values` / `new_values` for detail panels, filters, or custom UI when `description` is not enough.

---

## Reference Enums

### Event codes

| Code | Key | Arabic label | Status |
|------|-----|--------------|--------|
| `1` | `created` | تم الإنشاء | ✅ Used |
| `2` | `updated` | تم التعديل | ✅ Used |
| `3` | `deleted` | تم الحذف | ✅ Used |
| `4` | `restored` | تم الاسترجاع | ⚠️ Reserved (trait supports it; no flow yet) |
| `5` | `login` | تسجيل دخول | ✅ Used |
| `6` | `logout` | تسجيل خروج | ✅ Used |
| `7` | `status_changed` | تغيير الحالة | ⚠️ Reserved (not recorded yet) |
| `8` | `assigned` | تم التعيين | ✅ Used |
| `9` | `approved` | تمت الموافقة | ⚠️ Reserved |
| `10` | `rejected` | تم الرفض | ⚠️ Reserved |
| `11` | `settled` | تمت التسوية | ⚠️ Reserved |
| `12` | `collected` | تم التحصيل | ⚠️ Reserved |
| `13` | `returned` | تم الإرجاع | ⚠️ Reserved |
| `14` | `exported` | تم التصدير | ⚠️ Reserved |
| `15` | `password_changed` | تغيير كلمة المرور | ✅ Used |
| `16` | `activated` | تم التفعيل | ✅ Used |
| `17` | `deactivated` | تم التعطيل | ✅ Used |

### Actor type codes

| Code | Key | Arabic label |
|------|-----|--------------|
| `1` | `super_admin` | مدير نظام |
| `2` | `shipping_company` | شركة شحن |
| `3` | `delivery_agent` | مندوب توصيل |
| `4` | `system` | نظام |

### Auditable types (entity table names)

| `auditable_type` | Arabic entity label | PK column (for linking) |
|------------------|----------------------|-------------------------|
| `users` | مستخدم | `user_id` |
| `roles` | دور | `id` (BIGINT) |
| `governorates` | محافظة | `governorate_id` |
| `cities` | مدينة | `city_id` |
| `shipping_companies` | شركة شحن | `shipping_company_id` |
| `delivery_agents` | مندوب توصيل | `delivery_agent_id` |
| `orders` | طلب | `order_id` |

---

## Metadata Scenarios (explicit `metadata` payloads)

These are the only flows that currently send a non-empty `metadata` object.

### 1. Login — `event: 5`, `auditable_type: users`

```json
{
  "metadata": {
    "ip": "192.168.1.1",
    "name": "أحمد محمد"
  },
  "old_values": null,
  "new_values": null
}
```

| Key | Type | Description |
|-----|------|-------------|
| `ip` | `string \| null` | Client IP at login time |
| `name` | `string` | Actor display name |

---

### 2. Logout — `event: 6`, `auditable_type: users`

```json
{
  "metadata": {
    "name": "أحمد محمد"
  },
  "old_values": null,
  "new_values": null
}
```

| Key | Type | Description |
|-----|------|-------------|
| `name` | `string` | Actor display name |

---

### 3. Password changed by admin — `event: 15`, `auditable_type: users`

```json
{
  "metadata": {
    "changed_by_admin": true
  },
  "old_values": null,
  "new_values": null
}
```

| Key | Type | Description |
|-----|------|-------------|
| `changed_by_admin` | `boolean` | `true` when an admin reset another user's password |

> Self-service password change (`ChangePasswordUseCase`) records the same event but **`metadata` is `null`**.

---

### 4. Role permissions sync — `event: 2`, `auditable_type: roles`

```json
{
  "metadata": {
    "action": "sync_permissions",
    "role_name": "warehouse_manager"
  },
  "old_values": {
    "permissions": ["orders.view", "orders.create"]
  },
  "new_values": {
    "permissions": ["orders.view", "orders.create", "orders.assign"]
  }
}
```

| Key | Type | Description |
|-----|------|-------------|
| `action` | `"sync_permissions"` | Discriminator — always this value for permission sync |
| `role_name` | `string` | Spatie role name (used in description) |

`old_values.permissions` / `new_values.permissions` are arrays of permission name strings.

---

### 5. Governorate activate/deactivate — `event: 16 \| 17`, `auditable_type: governorates`

```json
{
  "metadata": {
    "name_en": "Cairo"
  },
  "old_values": { "is_active": true },
  "new_values": { "is_active": false }
}
```

| Key | Type | Description |
|-----|------|-------------|
| `name_en` | `string` | English name (subject label in description) |

---

### 6. City activate/deactivate — `event: 16 \| 17`, `auditable_type: cities`

```json
{
  "metadata": {
    "name_en": "Nasr City",
    "governorate_id": "uuid"
  },
  "old_values": { "is_active": true },
  "new_values": { "is_active": false }
}
```

| Key | Type | Description |
|-----|------|-------------|
| `name_en` | `string` | English city name |
| `governorate_id` | `string` (UUID) | Parent governorate — resolve for display if needed |

---

### 7. Order agent assignment — `event: 8`, `auditable_type: orders`

Triggered by admin assigning or reassigning a delivery agent.

```json
{
  "metadata": {
    "action": "order_agent_assignment",
    "reference_code": "ORD-2026-001",
    "is_reassignment": false,
    "previous_agent_id": null,
    "previous_agent_name": null,
    "new_agent_id": "uuid",
    "new_agent_name": "محمد علي"
  },
  "old_values": {
    "delivery_agent_id": null,
    "agent_name": null,
    "status": 1
  },
  "new_values": {
    "delivery_agent_id": "uuid",
    "agent_name": "محمد علي",
    "status": 2
  }
}
```

| Key | Type | Description |
|-----|------|-------------|
| `action` | `"order_agent_assignment"` | Discriminator — always this value |
| `reference_code` | `string` | Order reference shown to users |
| `is_reassignment` | `boolean` | `true` when replacing an existing agent |
| `previous_agent_id` | `string \| null` | UUID of former agent |
| `previous_agent_name` | `string \| null` | Former agent display name |
| `new_agent_id` | `string` | UUID of assigned agent |
| `new_agent_name` | `string` | Assigned agent display name |

**`old_values` / `new_values` fields**

| Key | Type | Map |
|-----|------|-----|
| `delivery_agent_id` | `string \| null` | Agent UUID |
| `agent_name` | `string \| null` | Agent user name |
| `status` | `number` | Order status ID — see [Order status codes](#order-status-codes) |

When `metadata.is_reassignment === true`, description uses `previous_agent_name` → `new_agent_name`.

---

## Scenarios with `metadata: null` (use `old_values` / `new_values` only)

### User activate/deactivate — `event: 16 \| 17`, `auditable_type: users`

```json
{
  "metadata": null,
  "old_values": { "is_active": true },
  "new_values": { "is_active": false }
}
```

| Key | Type | Map |
|-----|------|-----|
| `is_active` | `boolean` | `true` = active, `false` = inactive |

---

### Self password change — `event: 15`, `auditable_type: users`

```json
{
  "metadata": null,
  "old_values": null,
  "new_values": null
}
```

Password values are never stored. Rely on `description` and `event.code`.

---

### Role CRUD — `auditable_type: roles`

**Create** (`event: 1`)

```json
{
  "new_values": {
    "name": "warehouse_manager",
    "guard_name": "api"
  }
}
```

**Update name** (`event: 2`)

```json
{
  "old_values": { "name": "old_name" },
  "new_values": { "name": "new_name" }
}
```

**Delete** (`event: 3`)

```json
{
  "old_values": {
    "name": "warehouse_manager",
    "guard_name": "api",
    "permissions": ["orders.view", "orders.create"]
  }
}
```

---

### Governorate CRUD — `auditable_type: governorates`

**Create / Update / Delete** snapshot fields:

| Key | Type | Description |
|-----|------|-------------|
| `name_ar` | `string` | Arabic name |
| `name_en` | `string` | English name |
| `code` | `string` | Short code |
| `is_active` | `boolean` | Active flag |

- **Create** (`event: 1`): `new_values` = full snapshot
- **Update** (`event: 2`): `old_values` = changed fields before, `new_values` = full snapshot after
- **Delete** (`event: 3`): `old_values` = full snapshot

---

### City CRUD — `auditable_type: cities`

**Create / Update / Delete** snapshot fields:

| Key | Type | Description |
|-----|------|-------------|
| `governorate_id` | `string` (UUID) | Parent governorate |
| `name_ar` | `string` | Arabic name |
| `name_en` | `string` | English name |
| `code` | `string` | Short code |
| `is_active` | `boolean` | Active flag |

Same create/update/delete pattern as governorates.

---

## Automatic model auditing (`Auditable` trait)

Models `users`, `shipping_companies`, and `delivery_agents` auto-record on Eloquent lifecycle events. **`metadata` is always `null`.**

| Model table | Events fired |
|-------------|--------------|
| `users` | `created` (1), `updated` (2), `deleted` (3), `restored` (4) |
| `shipping_companies` | same |
| `delivery_agents` | same |

### Excluded fields (never in old/new values)

```
password, remember_token, fcm_token, email_verified_at
```

### `users` — possible keys in old/new values

| Key | Type | Map |
|-----|------|-----|
| `user_id` | `string` | UUID |
| `name` | `string` | Display name |
| `email` | `string \| null` | Email |
| `phone` | `string` | Phone |
| `avatar` | `string \| null` | Avatar path/URL |
| `gender` | `string \| null` | Gender |
| `is_active` | `boolean` | Active flag |
| `account_type` | `number` | See [Account type codes](#account-type-codes) |
| `welcome_whatsapp_url` | `string \| null` | WhatsApp welcome link |
| `last_login_at` | `string \| null` | ISO datetime |
| `created_at` | `string` | ISO datetime |
| `updated_at` | `string` | ISO datetime |
| `deleted_at` | `string \| null` | ISO datetime (soft delete) |

> **Note:** User create/update also fires this trait. `ToggleUserStatusUseCase` records a separate `activated`/`deactivated` event with only `is_active` — you may see both for the same action.

### `shipping_companies` — possible keys

| Key | Type | Map |
|-----|------|-----|
| `shipping_company_id` | `string` | UUID |
| `user_id` | `string` | Linked user UUID |
| `company_name` | `string` | Company name |
| `commercial_reg` | `string \| null` | Commercial registration |
| `logo_url` | `string \| null` | Logo URL |
| `commission_type` | `number` | `1` = percentage, `2` = fixed |
| `commission_value` | `string` | Decimal string (e.g. `"5.0000"`) |
| `balance` | `string` | Decimal string (e.g. `"1500.00"`) |
| `is_active` | `number` | `1` = active, `0` = inactive |
| `created_at` / `updated_at` / `deleted_at` | `string` | Timestamps |

### `delivery_agents` — possible keys

| Key | Type | Map |
|-----|------|-----|
| `delivery_agent_id` | `string` | UUID |
| `user_id` | `string` | Linked user UUID |
| `supervisor_agent_id` | `string \| null` | Supervisor agent UUID |
| `national_id` | `string \| null` | National ID |
| `vehicle_type` | `number` | See [Vehicle type codes](#vehicle-type-codes) |
| `vehicle_plate_number` | `string \| null` | Plate number |
| `commission_type` | `number` | `1` = percentage, `2` = fixed |
| `commission_value` | `string` | Decimal string |
| `balance` | `string` | Decimal string |
| `is_available` | `number` | `1` = available, `0` = unavailable |
| `created_at` / `updated_at` / `deleted_at` | `string` | Timestamps |

For **updated** events, only changed columns appear in `old_values` / `new_values` (not the full row).

---

## Value maps for coded fields

### Account type codes (`users.account_type`)

| Code | Key | Arabic |
|------|-----|--------|
| `1` | `super_admin` | مدير نظام |
| `2` | `shipping_company` | شركة شحن |
| `3` | `delivery_agent` | مندوب توصيل |
| `4` | `staff_member` | موظف إداري |

### Commission type codes

| Code | Meaning |
|------|---------|
| `1` | Percentage |
| `2` | Fixed amount |

### Vehicle type codes

| Code | Arabic |
|------|--------|
| `1` | دراجة نارية |
| `2` | سيارة |

### Order status codes (`orders.status` in assignment logs)

| Code | Key | Arabic |
|------|-----|--------|
| `1` | `pending` | بانتظار التوزيع |
| `2` | `assigned` | معيّن لمندوب |
| `3` | `out_for_delivery` | خرج للتوصيل |
| `4` | `awaiting_approval` | بانتظار الموافقة |
| `5` | `delivered` | تم التوصيل |
| `6` | `delivered_price_changed` | تم التوصيل بتغيير سعر |
| `7` | `partial_delivery` | تسليم جزئي |
| `8` | `refused_paid_shipping` | رفض + دفع رسوم الشحن |
| `9` | `refused_no_payment` | رفض وعدم دفع رسوم الشحن |
| `10` | `customer_cancelled` | ألغى العميل |
| `11` | `no_answer` | لا يوجد رد |
| `12` | `phone_off` | الهاتف مغلق |
| `13` | `customer_evading` | تهرّب / مختفي |
| `14` | `unsafe_area` | منطقة غير آمنة |
| `15` | `postponed` | مؤجل |
| `16` | `outside_governorate` | خارج المحافظة |
| `17` | `wrong_phone` | رقم هاتف خاطئ |

### Boolean display (server-side convention)

| Raw value | Arabic label used in `description` |
|-----------|--------------------------------------|
| `true` / `1` | نشط |
| `false` / `0` | غير نشط |
| `null` / `""` | فارغ |

---

## Field label keys (for custom diff UI)

Backend Arabic field labels live in `audit_logs::fields.*`. Use these keys when rendering `old_values` / `new_values` field names:

| Field key | Arabic label |
|-----------|--------------|
| `name` | الاسم |
| `name_ar` | الاسم بالعربية |
| `name_en` | الاسم بالإنجليزية |
| `email` | البريد الإلكتروني |
| `phone` | الهاتف |
| `gender` | الجنس |
| `avatar` | الصورة |
| `is_active` | الحالة |
| `is_available` | التوفر |
| `code` | الرمز |
| `guard_name` | الحارس |
| `permissions` | الصلاحيات |
| `governorate_id` | المحافظة |
| `company_name` | اسم الشركة |
| `commercial_reg` | السجل التجاري |
| `balance` | الرصيد |
| `commission_type` | نوع العمولة |
| `commission_value` | قيمة العمولة |
| `national_id` | الرقم القومي |
| `vehicle_type` | نوع المركبة |
| `vehicle_plate_number` | رقم اللوحة |
| `supervisor_agent_id` | المندوب المشرف |
| `status_id` | الحالة |
| `status` | الحالة |
| `delivery_agent_id` | مندوب التوصيل |
| `agent_name` | اسم المندوب |
| `last_login_at` | آخر تسجيل دخول |
| `logo_url` | الشعار |

---

## Metadata `action` discriminator summary

Use `metadata.action` when present to branch custom UI logic:

| `metadata.action` | Event | Entity | Meaning |
|-------------------|-------|--------|---------|
| `sync_permissions` | `2` (updated) | `roles` | Role permissions were replaced |
| `order_agent_assignment` | `8` (assigned) | `orders` | Agent assigned or reassigned |

---

## Special metadata key: `description` override

If `metadata.description` is set (not used in current flows), the server uses it as the full `description` text instead of auto-generating one. Reserved for future manual audit entries.

---

*Generated from backend source — `AuditLog` module, June 2026.*
