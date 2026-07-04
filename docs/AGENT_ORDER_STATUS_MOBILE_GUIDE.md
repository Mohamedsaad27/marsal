# Agent Order Status Mobile API Guide

This guide is for the Flutter mobile developer implementing delivery-agent order screens and status actions.

All endpoints below are for delivery agents only.

```http
Authorization: Bearer {jwt_token}
Accept: application/json
Content-Type: application/json
```

Base route:

```text
/api/v1/agent
```

Standard success response:

```json
{
  "isSuccess": true,
  "message": "Success message",
  "data": {}
}
```

Standard error response:

```json
{
  "isSuccess": false,
  "message": "Error message",
  "errors": {}
}
```

## 1. Load Status Definitions

Use this endpoint when the app starts, or before rendering status/action UI.

```http
GET /api/v1/agent/definitions
```

Response:

```json
{
  "isSuccess": true,
  "message": "Definitions loaded successfully",
  "data": {
    "order_statuses": [
      {
        "id": 2,
        "code": "assigned",
        "label_ar": "تم التعيين",
        "is_terminal": false,
        "requires_collection": false,
        "badge_color": "blue",
        "available_actions": ["start_delivery", "postpone", "call_customer"]
      }
    ],
    "collection_types": [
      { "id": 1, "code": "cod", "label_ar": "مبلغ الاستلام (COD)" },
      { "id": 2, "code": "shipping_fee", "label_ar": "رسوم الشحن" },
      { "id": 3, "code": "partial", "label_ar": "تحصيل جزئي" }
    ],
    "proof_file_types": [
      { "id": 1, "code": "image", "label_ar": "صورة" },
      { "id": 2, "code": "pdf", "label_ar": "ملف PDF" },
      { "id": 3, "code": "other", "label_ar": "أخرى" }
    ],
    "order_list_filters": [
      { "key": "all", "label_ar": "الكل", "status_ids": [1, 2, 3, 4, 11, 12, 14, 15, 16, 17] },
      { "key": "new", "label_ar": "جديد", "status_ids": [1, 2] },
      { "key": "in_delivery", "label_ar": "قيد التوصيل", "status_ids": [3] },
      { "key": "postponed", "label_ar": "مؤجل", "status_ids": [15] }
    ],
    "collection_settled_filters": [
      { "key": "unsettled", "query_value": "false", "label_ar": "غير مسواة" },
      { "key": "settled", "query_value": "true", "label_ar": "مسواة" }
    ],
    "refusal_resolutions": [
      { "id": 1, "code": "delivered", "label_ar": "وافق العميل أثناء المؤقت", "result_status_id": 5, "requires_collection": true },
      { "id": 2, "code": "refused_paid", "label_ar": "رفض ودفع رسوم الشحن", "result_status_id": 8, "requires_collection": true },
      { "id": 3, "code": "refused_no_pay", "label_ar": "رفض وعدم الدفع", "result_status_id": 9, "requires_collection": false },
      { "id": 4, "code": "expired", "label_ar": "انتهى المؤقت بدون حل", "result_status_id": 9, "requires_collection": false }
    ]
  }
}
```

The app should use `available_actions` from the current order status to decide which buttons to show.

## 2. List Orders By Status Type

```http
GET /api/v1/agent/orders?status={filter}&search={text}&per_page=20
```

Available `status` filter values:

| Filter | Meaning | Status IDs returned |
| --- | --- | --- |
| `all` or empty | Active orders | `1,2,3,4,11,12,14,15,16,17` |
| `new` | New/assigned orders | `1,2` |
| `in_delivery` | Orders currently out for delivery | `3` |
| `postponed` | Postponed orders | `15` |

Response:

```json
{
  "isSuccess": true,
  "message": "Orders loaded successfully",
  "data": {
    "items": [
      {
        "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
        "internal_code": "ORD-10024",
        "status": {
          "id": 3,
          "label": "قيد التوصيل",
          "color": "blue"
        },
        "customer_name": "Ahmed Ali",
        "customer_phone": "01000000000",
        "city": "Nasr City",
        "governorate": "Cairo",
        "cod_amount": 450.0,
        "expected_delivery_date": "2026-07-05"
      }
    ],
    "type": "length_aware",
    "current_page": 1,
    "last_page": 4,
    "per_page": 20,
    "total": 73,
    "from": 1,
    "to": 20,
    "has_more": true
  }
}
```

## 3. Order Details

```http
GET /api/v1/agent/orders/{order_id}
```

Response:

```json
{
  "isSuccess": true,
  "message": "Order details loaded successfully",
  "data": {
    "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
    "reference_code": "ORD-10024",
    "company_name": "Mersal Test Company",
    "status": {
      "id": 3,
      "label": "قيد التوصيل"
    },
    "customer": {
      "name": "Ahmed Ali",
      "phone_1": "01000000000",
      "phone_2": "01100000000"
    },
    "address": {
      "governorate": "Cairo",
      "city": "Nasr City",
      "address_line": "Street 10, Building 5"
    },
    "financials": {
      "cod_amount": 450.0,
      "shipping_fee": 50.0,
      "collected_amount": null,
      "total_due": 450.0
    },
    "items": {
      "quantity": 2,
      "description": "Clothes package",
      "delivered_quantity": null
    },
    "schedule": {
      "expected_delivery_date": "2026-07-05",
      "postponed_date": null
    },
    "approvals": {
      "requires_approval": false,
      "approval_granted": null
    },
    "status_history": [
      {
        "status_id": 2,
        "status_label": "تم التعيين",
        "changed_at": "2026-07-04T10:30:00.000000Z",
        "changed_by": "Admin User",
        "notes": null
      }
    ],
    "proof_photos": [],
    "available_actions": [
      "confirm_delivery",
      "refuse",
      "no_answer",
      "phone_off",
      "unsafe_area",
      "outside_governorate",
      "wrong_phone",
      "postpone",
      "call_customer"
    ]
  }
}
```

## 4. Status IDs And Meanings

| ID | Code | Arabic label | Terminal | Needs collection | Notes |
| --- | --- | --- | --- | --- | --- |
| 1 | `pending` | بانتظار التوزيع | No | No | Usually before assignment. Not directly actionable by agent. |
| 2 | `assigned` | تم التعيين | No | No | Agent can start delivery or postpone. |
| 3 | `out_for_delivery` | قيد التوصيل | No | No | Main working status. Most status actions start from here. |
| 4 | `awaiting_approval` | بانتظار الموافقة | No | No | Created when agent requests delivered with changed price. |
| 5 | `delivered` | تم التسليم | Yes | Yes | Successful delivery with normal amount. |
| 6 | `delivered_price_changed` | تم التسليم بتغيير سعر | Yes in business meaning | Yes | Agent requests this, but backend stores status `4` until admin approval. |
| 7 | `partial_delivery` | تسليم جزئي | Yes | Yes | Customer received part of the order. |
| 8 | `refused_paid_shipping` | رفض + دفع رسوم الشحن | Yes | Yes | Customer refused but paid shipping fee. |
| 9 | `refused_no_payment` | رفض وعدم دفع رسوم الشحن | Yes | No | Customer refused and paid nothing. |
| 10 | `customer_cancelled` | ألغى العميل | Yes | No | Customer cancelled. |
| 11 | `no_answer` | لا يوجد رد | No | No | Customer did not answer. |
| 12 | `phone_off` | الهاتف مغلق | No | No | Customer phone is switched off. |
| 14 | `unsafe_area` | منطقة غير آمنة | No | No | Agent cannot safely deliver. |
| 15 | `postponed` | مؤجل | No | No | Requires `postponed_date`. |
| 16 | `outside_governorate` | خارج المحافظة | No | No | Address is outside governorate. |
| 17 | `wrong_phone` | رقم هاتف خاطئ | No | No | Customer phone is invalid/wrong. |

Status `13` (`customer_evading`) is retired in the backend and should not be used by the mobile app.

## 5. Allowed Agent Transitions

The backend validates transitions. Do not send any status that is not allowed from the current order status.

| Current status | Allowed next status IDs |
| --- | --- |
| `2 assigned` | `3 out_for_delivery`, `15 postponed` |
| `3 out_for_delivery` | `5 delivered`, `6 delivered_price_changed`, `7 partial_delivery`, `8 refused_paid_shipping`, `9 refused_no_payment`, `10 customer_cancelled`, `11 no_answer`, `12 phone_off`, `14 unsafe_area`, `15 postponed`, `16 outside_governorate`, `17 wrong_phone` |
| `4 awaiting_approval` | No status update by agent. App may show call customer only. |
| Terminal statuses `5,6,7,8,9,10` | No next status by agent. |

## 6. Update Order Status

```http
PATCH /api/v1/agent/orders/{order_id}/status
```

Base body:

```json
{
  "status_id": 3,
  "notes": "Optional note, max 500 chars"
}
```

Success response:

```json
{
  "isSuccess": true,
  "message": "Status updated successfully",
  "data": {
    "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
    "new_status": {
      "id": 3,
      "label": "قيد التوصيل"
    },
    "collection_created": false
  }
}
```

Important: for `status_id = 6` (`delivered_price_changed`), the success response usually returns `new_status.id = 4` because the order is stored as `awaiting_approval` until admin review.

### 6.1 Start Delivery

Use when current status is `2 assigned`.

Request:

```json
{
  "status_id": 3,
  "notes": "Started delivery route"
}
```

Response data:

```json
{
  "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
  "new_status": { "id": 3, "label": "قيد التوصيل" },
  "collection_created": false
}
```

### 6.2 Delivered

Use when current status is `3 out_for_delivery`.

Required fields:

| Field | Type | Value |
| --- | --- | --- |
| `status_id` | integer | `5` |
| `collected_amount` | number | Required, `>= 0` |
| `collection_type` | integer | Required, usually `1` (`cod`) |
| `notes` | string/null | Optional |

Request:

```json
{
  "status_id": 5,
  "collected_amount": 450,
  "collection_type": 1,
  "notes": "Delivered and collected COD"
}
```

Response data:

```json
{
  "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
  "new_status": { "id": 5, "label": "تم التسليم" },
  "collection_created": true
}
```

### 6.3 Delivered With Price Change

Use when current status is `3 out_for_delivery` and the customer pays a different COD amount.

Required fields:

| Field | Type | Value |
| --- | --- | --- |
| `status_id` | integer | `6` |
| `collected_amount` | number | Required, amount actually collected |
| `collection_type` | integer | Required, usually `1` (`cod`) |
| `new_cod_amount` | number | Required, new agreed COD amount |
| `notes` | string/null | Recommended |

Request:

```json
{
  "status_id": 6,
  "collected_amount": 400,
  "collection_type": 1,
  "new_cod_amount": 400,
  "notes": "Customer accepted lower amount after approval request"
}
```

Response data:

```json
{
  "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
  "new_status": { "id": 4, "label": "بانتظار الموافقة" },
  "collection_created": false
}
```

The backend creates an approval request. The order remains `awaiting_approval` until admin approves or rejects.

### 6.4 Partial Delivery

Use when current status is `3 out_for_delivery` and only part of the order is delivered.

Required fields:

| Field | Type | Value |
| --- | --- | --- |
| `status_id` | integer | `7` |
| `collected_amount` | number | Required, amount collected for delivered part |
| `collection_type` | integer | Required, use `3` (`partial`) |
| `notes` | string/null | Recommended |

Request:

```json
{
  "status_id": 7,
  "collected_amount": 250,
  "collection_type": 3,
  "notes": "Customer received one item only"
}
```

Response data:

```json
{
  "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
  "new_status": { "id": 7, "label": "تسليم جزئي" },
  "collection_created": true
}
```

### 6.5 Refused And Paid Shipping

Use when current status is `3 out_for_delivery` and customer refuses the order but pays shipping.

Required fields:

| Field | Type | Value |
| --- | --- | --- |
| `status_id` | integer | `8` |
| `collected_amount` | number | Required, shipping fee collected |
| `collection_type` | integer | Required, use `2` (`shipping_fee`) |
| `notes` | string/null | Recommended |

Request:

```json
{
  "status_id": 8,
  "collected_amount": 50,
  "collection_type": 2,
  "notes": "Customer refused and paid shipping fee"
}
```

Response data:

```json
{
  "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
  "new_status": { "id": 8, "label": "رفض + دفع رسوم الشحن" },
  "collection_created": true
}
```

### 6.6 Refused No Payment

Use when current status is `3 out_for_delivery` and customer refuses without paying.

Request:

```json
{
  "status_id": 9,
  "notes": "Customer refused and did not pay shipping"
}
```

Response data:

```json
{
  "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
  "new_status": { "id": 9, "label": "رفض وعدم دفع رسوم الشحن" },
  "collection_created": false
}
```

### 6.7 Customer Cancelled

Use when current status is `3 out_for_delivery`.

Request:

```json
{
  "status_id": 10,
  "notes": "Customer cancelled the order"
}
```

Response data:

```json
{
  "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
  "new_status": { "id": 10, "label": "ألغى العميل" },
  "collection_created": false
}
```

### 6.8 No Answer

Use when current status is `3 out_for_delivery`.

Request:

```json
{
  "status_id": 11,
  "notes": "Called twice, no answer"
}
```

Response data:

```json
{
  "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
  "new_status": { "id": 11, "label": "لا يوجد رد" },
  "collection_created": false
}
```

### 6.9 Phone Off

Use when current status is `3 out_for_delivery`.

Request:

```json
{
  "status_id": 12,
  "notes": "Customer phone is switched off"
}
```

Response data:

```json
{
  "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
  "new_status": { "id": 12, "label": "الهاتف مغلق" },
  "collection_created": false
}
```

### 6.10 Unsafe Area

Use when current status is `3 out_for_delivery`.

Request:

```json
{
  "status_id": 14,
  "notes": "Area was unsafe for delivery"
}
```

Response data:

```json
{
  "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
  "new_status": { "id": 14, "label": "منطقة غير آمنة" },
  "collection_created": false
}
```

### 6.11 Postponed

Use when current status is `2 assigned` or `3 out_for_delivery`.

Required fields:

| Field | Type | Value |
| --- | --- | --- |
| `status_id` | integer | `15` |
| `postponed_date` | date | Required, must be after today |
| `notes` | string/null | Optional |

Request:

```json
{
  "status_id": 15,
  "postponed_date": "2026-07-06",
  "notes": "Customer requested delivery tomorrow"
}
```

Response data:

```json
{
  "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
  "new_status": { "id": 15, "label": "مؤجل" },
  "collection_created": false
}
```

There is also a dedicated reschedule endpoint:

```http
PATCH /api/v1/agent/orders/{order_id}/reschedule
```

Use it when editing an already postponed schedule instead of changing the order status for the first time.

### 6.12 Outside Governorate

Use when current status is `3 out_for_delivery`.

Request:

```json
{
  "status_id": 16,
  "notes": "Address is outside the assigned governorate"
}
```

Response data:

```json
{
  "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
  "new_status": { "id": 16, "label": "خارج المحافظة" },
  "collection_created": false
}
```

### 6.13 Wrong Phone

Use when current status is `3 out_for_delivery`.

Request:

```json
{
  "status_id": 17,
  "notes": "Phone number is wrong"
}
```

Response data:

```json
{
  "order_id": "9b7d7a5e-6f5c-4f1d-a3e9-6656d2e61311",
  "new_status": { "id": 17, "label": "رقم هاتف خاطئ" },
  "collection_created": false
}
```

## 7. Validation Errors

Example: missing collection fields for delivered status.

Request:

```json
{
  "status_id": 5
}
```

Response:

```json
{
  "isSuccess": false,
  "message": "The given data was invalid.",
  "errors": {
    "collected_amount": ["The collected amount field is required."],
    "collection_type": ["The collection type field is required."]
  }
}
```

Example: invalid transition.

```json
{
  "isSuccess": false,
  "message": "Transition from Assigned to Delivered is not allowed.",
  "errors": null
}
```

## 8. Mobile Implementation Notes

- Use `GET /api/v1/agent/definitions` as the source of truth for status IDs, colors, filters, and action buttons.
- Use `GET /api/v1/agent/orders?status=new` for the New tab.
- Use `GET /api/v1/agent/orders?status=in_delivery` for the In Delivery tab.
- Use `GET /api/v1/agent/orders?status=postponed` for the Postponed tab.
- Use `GET /api/v1/agent/orders/{order_id}` before showing the action sheet, because `available_actions` is returned per order.
- For statuses `5, 6, 7, 8`, always show collection amount and collection type inputs.
- For status `6`, also show `new_cod_amount`.
- For status `15`, show a future date picker and send `postponed_date`.
- After any successful status update, refresh the order detail and the current list tab.
- Do not send status `13`; it is retired.
- Do not hardcode Arabic labels in Flutter if the definitions endpoint is available.
