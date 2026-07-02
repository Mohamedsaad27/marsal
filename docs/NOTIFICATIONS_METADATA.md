# Notifications — Frontend Value Mapping Reference

Reference for mapping `type`, `data`, and counters returned by the Notifications APIs.

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/v1/notifications` | Paginated notifications list + KPI counters |
| `GET` | `/api/v1/notifications/unread-count` | Unread badge count |
| `PATCH` | `/api/v1/notifications/{notificationId}/read` | Mark single notification as read |
| `PATCH` | `/api/v1/notifications/read-all` | Mark all notifications as read |
| `DELETE` | `/api/v1/notifications/read` | Delete all read notifications |

---

## Notification Item Shape

Each item in `GET /api/v1/notifications` (`NotificationResource`) has this shape:

```json
{
  "id": "uuid",
  "type": {
    "code": 6,
    "label": "رسالة جديدة"
  },
  "title_ar": "💬 رسالة جديدة",
  "body_ar": "أرسل أحمد رسالة جديدة في محادثة الطلب ORD-001 — اضغط للاطلاع",
  "data": {
    "order_id": "uuid",
    "conversation_id": "uuid"
  },
  "is_read": false,
  "sent_via_fcm": true,
  "read_at": null,
  "created_at": "2026-07-02T09:30:00.000000Z"
}
```

| Field | Type | Notes |
|-------|------|-------|
| `id` | `string` (UUID) | Notification primary key (`notification_id`) |
| `type.code` | `number` | See [Notification type codes](#notification-type-codes) |
| `type.label` | `string` | Arabic label from enum |
| `title_ar` | `string` | Arabic title |
| `body_ar` | `string` | Arabic body |
| `data` | `object` | Type-dependent payload; defaults to `{}` |
| `is_read` | `boolean` | Read status |
| `sent_via_fcm` | `boolean` | Whether an FCM send was requested |
| `read_at` | `string \| null` | ISO datetime |
| `created_at` | `string` | ISO datetime |

---

## List/KPI Response Shape

`GET /api/v1/notifications` returns:

```json
{
  "status": true,
  "message": "تم جلب الإشعارات بنجاح",
  "data": {
    "kpis": {
      "approvals": 2,
      "collections": 4,
      "shipments": 8,
      "settlements": 1,
      "unread": 15
    },
    "items": [],
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 14,
    "has_more": false
  }
}
```

### KPI keys

| Key | Type | Meaning |
|-----|------|---------|
| `approvals` | `number` | Unread notifications in approvals bucket |
| `collections` | `number` | Unread notifications in collections bucket |
| `shipments` | `number` | Unread notifications in shipments bucket |
| `settlements` | `number` | Unread notifications in settlements bucket (التسويات) |
| `unread` | `number` | Total unread notifications |

### KPI category → notification types

| KPI key | Arabic label | Type codes |
|---------|--------------|------------|
| `approvals` | موافقات | `3`, `4`, `5` |
| `collections` | تحصيلات | `9` |
| `shipments` | شحنات | `1`, `2`, `6`, `7`, `8`, `11`, `12` |
| `settlements` | التسويات | `10` |

---

## Notification Type Codes

| Code | Enum key | Arabic label | Typical recipients |
|------|----------|--------------|--------------------|
| `1` | `new_order` | طلب توصيل جديد | Delivery agent |
| `2` | `status_change` | تحديث حالة الطلب | Shipping company, Super admin |
| `3` | `approval_request` | طلب موافقة على تغيير السعر | Shipping company, Super admin |
| `4` | `timer_start` | بدأ توقيت رفض الاستلام | Shipping company, Super admin |
| `5` | `timer_expired` | انتهى وقت رفض الاستلام | Shipping company + Delivery agent + Super admin |
| `6` | `new_message` | رسالة جديدة | Any user |
| `7` | `phone_updated` | تم تحديث رقم الهاتف | Delivery agent |
| `8` | `postponed_reminder` | تذكير بموعد تأجيل التسليم | Delivery agent |
| `9` | `collected` | تحصيل نقدي من المندوب | Super admin |
| `10` | `settled` | تسوية مالية | Super admin |
| `11` | `returned` | مرتجع | Super admin |
| `12` | `order_reassigned` | إعادة تعيين طلب | Super admin |

> Note: DB migration comment may still mention only old types `1..8`, but current source enum defines `1..12` and is the source of truth.

---

## `data` Payload Schemas By Type

Use this table for frontend routing and deep links.

| `type.code` | `data` shape | Notes |
|-------------|--------------|-------|
| `1` (`new_order`) | `{ "order_id": "uuid" }` | Open order details |
| `2` (`status_change`) | `{ "order_id": "uuid" }` or `{ "order_id": "uuid", "approval_request_id": "uuid" }` | Second shape is used when approval request gets reviewed |
| `3` (`approval_request`) | `{ "order_id": "uuid" }` | Open order + approval tab |
| `4` (`timer_start`) | `{ "order_id": "uuid" }` | Timer started for refusal flow |
| `5` (`timer_expired`) | `{ "order_id": "uuid" }` | Timer expired |
| `6` (`new_message`) | `{ "order_id": "uuid", "conversation_id": "uuid" }` | Open chat conversation |
| `7` (`phone_updated`) | `{}` | Static informational alert |
| `8` (`postponed_reminder`) | `{ "order_id": "uuid" }` | Open postponed order |
| `9` (`collected`) | `{ "order_id": "uuid", "collection_id": "uuid" }` | Super admin collections context |
| `10` (`settled`) | `{ "settlement_id": "uuid" }` | Settlement created/paid |
| `11` (`returned`) | `{ "return_id": "uuid", "order_id": "uuid" }` | Return workflow |
| `12` (`order_reassigned`) | `{ "order_id": "uuid" }` | Reassignment tracking |

---

## Type-Specific Examples

### 1) New Order

```json
{
  "type": { "code": 1, "label": "طلب توصيل جديد" },
  "data": { "order_id": "uuid" }
}
```

### 2) Status Change (standard)

```json
{
  "type": { "code": 2, "label": "تحديث حالة الطلب" },
  "data": { "order_id": "uuid" }
}
```

### 2) Status Change (approval review result)

```json
{
  "type": { "code": 2, "label": "تحديث حالة الطلب" },
  "data": {
    "order_id": "uuid",
    "approval_request_id": "uuid"
  }
}
```

### 6) New Message

```json
{
  "type": { "code": 6, "label": "رسالة جديدة" },
  "data": {
    "order_id": "uuid",
    "conversation_id": "uuid"
  }
}
```

### 9) Collected (super admin)

```json
{
  "type": { "code": 9, "label": "تحصيل نقدي من المندوب" },
  "data": {
    "order_id": "uuid",
    "collection_id": "uuid"
  }
}
```

### 10) Settled (super admin)

```json
{
  "type": { "code": 10, "label": "تسوية مالية" },
  "data": {
    "settlement_id": "uuid"
  }
}
```

### 11) Returned (super admin)

```json
{
  "type": { "code": 11, "label": "مرتجع" },
  "data": {
    "return_id": "uuid",
    "order_id": "uuid"
  }
}
```

---

## Routing Hints For Frontend

Suggested handling based on `type.code`:

| Type | Suggested route/action |
|------|------------------------|
| `1,2,3,4,5,8,12` | Open order details page using `data.order_id` |
| `2` with `approval_request_id` | Open order details directly in approvals context |
| `6` | Open chat screen with `conversation_id` |
| `7` | Show informational modal/toast only |
| `9` | Open collections module with `collection_id` |
| `10` | Open settlements module with `settlement_id` |
| `11` | Open returns module with `return_id` |

---

## FCM Payload Note

When pushed via FCM, backend merges notification `data` and injects:

```json
{
  "type": "N"
}
```

Where `N` is the notification type code as **string** (`"1"` ... `"12"`).

This is in addition to DB notification data. Keep mobile/web handlers tolerant to both numeric and string type values.

---

## Template Variables (for text generation only)

`title_ar` and `body_ar` are already rendered by backend templates. Frontend usually does not need these variables, but for reference:

| Type | Variables used in templates |
|------|------------------------------|
| `1` | `order_code` |
| `2` | `order_code`, `status_label` |
| `3` | `agent_name`, `order_code`, `new_amount` |
| `4` | `agent_name`, `order_code`, `minutes` |
| `5` | `order_code` |
| `6` | `sender_name`, `order_code` |
| `7` | none |
| `8` | `order_code`, `date` |
| `9` | `agent_name`, `order_code`, `collected_amount` (super admin template) |
| `10` | `settlement_action`, `entity_label`, `net_amount` (super admin template) |
| `11` | `return_action`, `order_code`, `agent_name` (super admin template) |
| `12` | `order_code`, `old_agent`, `new_agent` (super admin template) |

---

## Defensive Parsing Rules

- Treat missing `data` as `{}`.
- Ignore unknown extra keys in `data` for forward compatibility.
- Render unknown `type.code` as generic notification item using `title_ar` and `body_ar`.
- Prefer backend `type.label` for display text.

---

*Generated from backend source — Notifications module, July 2026.*
