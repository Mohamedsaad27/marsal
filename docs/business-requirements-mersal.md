# Business Requirements Document
## Shipping & Delivery Management System (Mersal)

> **Phase 1 — MVP Scope**  
> Confidential — Prepared by the Business Analysis Team

---

## 1. Users & Roles

### 1.1 Super Admin
> Highest authority in the system — full, unrestricted access to all data and operations.

**Responsibilities:**
- Order management: receive, distribute, track, and modify orders
- Delivery agent management: create, edit, activate/deactivate agents
- Shipping company management: register, configure, activate/deactivate companies
- Monitor collections and outstanding amounts
- Track returns and rejected orders
- Perform financial settlements with agents and companies
- Full access to all reports
- Manage notifications and system settings

---

### 1.2 Shipping Company
> Dedicated portal per company — restricted view to their own orders only.

**Capabilities:**
- Track all their orders in real time
- Approve or reject price/shipping fee change requests
- Edit order data (e.g., phone number)
- Communicate with agents and admin via the chat system
- View financial settlements related to their orders

---

### 1.3 Delivery Agent
> Dedicated mobile app — operates in the field in real time.

**Capabilities:**
- View orders assigned to them
- Update each order's status based on the actual delivery outcome
- Collect cash amounts and record them in the system
- Upload proof-of-delivery (waybill / signature / photo)
- Communicate with admin and shipping company via chat
- Schedule postponed orders on the in-app calendar

---

## 2. End-to-End Workflow

| Party | Action | Details |
|---|---|---|
| Shipping Company | Submit orders | Sent via portal — individually or in bulk — with customer data, amount, and address |
| Admin | Receive orders | Reviews incoming orders and verifies data completeness before distribution |
| Admin | Distribute orders | Assigns each order to a suitable agent based on area and availability |
| Agent | Receive notification | Gets an instant push notification for the new order in the mobile app |
| Agent | Attempt delivery | Proceeds to the address and attempts to hand over the order |
| Agent | Update status | Selects the appropriate status based on the delivery attempt outcome |
| Agent | Collect payment | Collects the specified amount according to order status and records it in the app |
| Admin | Receive collection | Receives the agent's total collected amount and records it in the system |
| Admin | Settlement | Calculates the net amount due after deducting commission and transfers it to the shipping company |

---

## 3. Delivery Scenarios

### 3.1 Delivered — Full Collection
- Agent delivers the order to the customer at the original amount
- **Collection = Full original amount**
- Commission calculated automatically from the collected amount
- Agent uploads proof-of-delivery photo

---

### 3.2 Delivered with Price Change
- Agent requests an amount change from the sender
- Shipping company sends an approval request to the shipping agent
- Agent **cannot** modify the price directly
- **Collection = Approved new amount only**
- Order is closed after shipping company confirmation

---

### 3.3 Partial Delivery
- Part of the goods are delivered; the rest is returned
- Delivered and returned quantities are recorded in the app
- Shipping company confirms the final amount for the delivered portion
- System automatically calculates: **Collection = Amount for delivered quantity**

---

### 3.4 Rejection — Customer Pays Shipping Fee
- Customer refuses to receive the order but agrees to pay the shipping fee
- Shipping company sets the shipping fee value
- **Collection = Shipping fee only**

---

### 3.5 Rejection — Customer Refuses to Pay Shipping Fee ⚠️ Complex Scenario

> **This scenario requires a special 5-minute timer flow.**

1. Agent initiates the rejection flow from the app
2. App starts a **5-minute countdown timer**
3. Instant notification sent to the shipping company about the rejection
4. Shipping company contacts the customer or agent to find a resolution
5. Agent must remain at the customer's location
6. **If timer expires without resolution:** Order is closed with status `Rejected — No Shipping Fee Paid`
7. **If resolved within the timer:** Agent completes delivery with the agreed-upon status

---

## 4. Order Statuses

### 4.1 Statuses with Collection

| Status | Description | Collection |
|---|---|---|
| Delivered | Full delivery at original amount | Full amount |
| Delivered with Price Change | Delivery at an approved modified amount | Modified amount |
| Partial Delivery | Part of the goods delivered | Amount for delivered portion |
| Rejected + Shipping Paid | Rejection with shipping fee collected | Shipping fee only |

---

### 4.2 Statuses without Collection

| Status | Description |
|---|---|
| Customer Cancelled | Customer cancelled the order in advance |
| No Answer | Customer is not responding to calls |
| Phone Off | Customer's phone is switched off |
| Evasive / Unreachable | Customer is avoiding contact and cannot be reached |
| Unsafe Area | Area poses a safety risk to the agent (GPS-logged) |
| Postponed | Postponed at customer's request to a specific date (date-logged) |
| Out of Coverage | Address is outside the delivery coverage area |
| Wrong Phone Number | The registered phone number is incorrect (triggers correction flow) |
| Rejected — No Shipping Paid | Full rejection with no amount collected |

---

### 4.3 Special Status Logic

**Postponed Orders:**
Agent selects a new date from the in-app calendar. The order is automatically re-assigned to the same agent on the specified date, with a reminder notification.

**Wrong Phone Number:**
The shipping company can edit the phone number directly from their portal. The order is automatically re-distributed after the correction.

---

## 5. Financial Flow & Settlements

### 5.1 Commission Formula

```
Net Due = Total Collections − Commission
```

---

### 5.2 Financial System Components

| Component | Description |
|---|---|
| Cash Collections | Amounts collected by agents from customers |
| Commissions | Percentage or fixed amount deducted per delivery |
| Shipping Fees | Fixed amount set by the shipping company in rejection cases |
| Settlements | Periodic clearing between admin, agents, and shipping companies |
| Returns | Tracking and financial documentation of returned goods |
| Balances | Real-time balance for each agent and each shipping company |

---

### 5.3 Money Flow Through the System

1. Agent collects amounts from customers and records them in the app immediately
2. Collections appear in the admin dashboard in real time
3. Agent hands over total collections to admin — daily or weekly
4. System deducts the agent's commission from total collections
5. Amounts due to each shipping company are transferred after settlement approval
6. System generates a detailed account statement for each party

---

## 6. Notification System

| Recipient | Trigger Event |
|---|---|
| Delivery Agent | New order assigned to them |
| Delivery Agent | Reminder for a postponed order |
| Delivery Agent | New chat message |
| Shipping Company | Any order status change |
| Shipping Company | Price change approval request |
| Shipping Company | Rejection timer started (5 minutes) |
| Shipping Company | Rejection timer expired |
| Shipping Company | Customer phone number updated |
| Admin | Daily collections report |
| Admin | Order requiring approval |

---

## 7. Mobile App — Delivery Agent

### 7.1 App Screens & Functions

| Screen | Function |
|---|---|
| Login (OTP + Password) | Secure login with two-factor authentication |
| Assigned Orders List | Priority-sorted order list |
| Order Details | Full data + map |
| Update Order Status | Status selection + required data inputs |
| Upload Proof of Delivery | Camera for uploading proof |
| Collect Payment | Amount entry and collection confirmation |
| Instant Chat | Text + images + attachments |
| Postponed Orders & Calendar | Schedule and track postponed orders |
| Balance Summary | Collections, commissions, and amount due |

---

## 8. Admin Dashboard

### 8.1 Admin Panel Modules

| Module | Description |
|---|---|
| Order Management | Interactive table with multi-filter (status, company, agent, date) |
| Distribution System | Manual or semi-automatic order assignment |
| Delivery Tracking | Aggregated map of delivery locations |
| Collections Management | Daily/weekly summary per agent |
| Financial Settlements | Generate and approve account statements |
| Returns Management | Returns list with complete data |
| Reports | Predefined + customizable reports |
| User Management | Add/edit/delete agents and shipping companies |
| Notification Center | Send bulk or individual notifications |
| Financial Summary | Real-time KPI dashboard |

---

### 8.2 Shipping Company Portal

- Upload orders (single or bulk)
- Track each order's status
- Approve price change requests
- Edit order data (phone number)
- Account statement and settlements
- Chat with admin and agents

---

## 9. Reports

### 9.1 Operational Reports

- Daily / weekly / monthly orders report (by status, area, company, agent)
- Agent performance report (success rate, average time, order count)
- Returns report (reasons, quantities, values)
- Rejection cases report (reason classification, geographic distribution)

### 9.2 Financial Reports

- Collections report (daily + total per agent)
- Commissions report (detailed per agent)
- Settlement statement per shipping company
- Current balance summary
- Collected shipping fees report

### 9.3 Export Options

- Excel export for all reports
- Direct print from the admin dashboard

---

## 10. Phase 1 MVP Scope

> Phase 1 represents the minimum viable product (MVP) that enables the system to operate from day one.

### ✅ In Scope

- Order management: receive, distribute, track, modify
- All 5 delivery scenarios
- All order statuses (with and without collection)
- 5-minute rejection timer system
- Postponed orders and calendar management
- Collections, commissions, and financial settlements
- Full-featured Flutter mobile app
- Admin control panel
- Internal chat system
- Instant push notifications (FCM)
- File and image uploads
- Basic reports
- User and permissions management
