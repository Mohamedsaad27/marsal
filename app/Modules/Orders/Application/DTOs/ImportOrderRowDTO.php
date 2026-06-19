<?php

namespace App\Modules\Orders\Application\DTOs;

readonly class ImportOrderRowDTO
{
    public function __construct(
        // ── Identity ──────────────────────────────────────────────────────
        public string $referenceNo,       // [0] الكود       → orders.reference_no
        // ── Customer ──────────────────────────────────────────────────────
        public string $customerName,      // [1] اسم العميل  → order_customer_info.customer_name
        public array  $customerPhones,    // [4] رقم التليفون → customer_phone / phone_alt
        // ── Address ───────────────────────────────────────────────────────
        public string $addressLine,       // [2] العنوان     → order_addresses.address_line
        public string $governorateName,   // [3] المحافظة    → order_addresses.governorate_id
        // ── Items ─────────────────────────────────────────────────────────
        public string $itemDescription,   // [5] وصف الشحنة  → orders.notes (order-level description)
        public int    $quantity,          // [6] عدد القطع   → order_items.total_quantity
        // ── Financials ────────────────────────────────────────────────────
        public float  $codAmount,         // [7] الإجمالي    → order_financials.original_amount
        // ── Company ───────────────────────────────────────────────────────
        public string $displayCompanyName,// [8] اسم الشركة  → stored in history note (display only)
        public string $companyName,       // [9] اسم الراسل  → shipping_company lookup / auto-create
        // ── Agent ─────────────────────────────────────────────────────────
        public string $agentName,         // [10] اسم المندوب → delivery_agents lookup
        // ── Status ────────────────────────────────────────────────────────
        public int    $statusId,          // [11] موقف العميل → orders.status (empty = pending/1)
        // ── Meta ──────────────────────────────────────────────────────────
        public int    $rowNumber,
    ) {}
}
