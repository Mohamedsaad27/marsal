<?php

namespace App\Modules\Orders\Application\UseCases;

use App\Modules\Orders\Application\DTOs\ImportOrderRowDTO;
use App\Modules\Orders\Application\Services\ReferenceCodeGeneratorService;
use App\Modules\Orders\Application\Validators\OrderRowValidator;
use App\Modules\Orders\Domain\Enums\ImportStatusHintEnum;
use App\Modules\Orders\Infrastructure\Imports\OrdersImport;
use App\Modules\Users\Domain\Enums\AccountTypeEnum;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ImportOrdersFromExcelUseCase
{
    public function __construct(
        private OrderRowValidator $validator,
        private ReferenceCodeGeneratorService $referenceCodeGenerator,
    ) {}

    public function execute(string $filePath, ?string $batchId = null): array
    {
        $batchId ??= (string) Str::uuid();

        $import = new OrdersImport();
        Excel::import($import, $filePath);
        $rows = $import->getRows();

        // ── Build lookup caches (avoid N+1 DB calls) ──────────────────────
        $governorateCache = DB::table('governorates')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->pluck('governorate_id', 'name_ar');

        $companyCache = DB::table('shipping_companies')
            ->whereNull('deleted_at')
            ->pluck('shipping_company_id', 'company_name');

        $agentCache = DB::table('delivery_agents as da')
            ->join('users as u', 'u.user_id', '=', 'da.user_id')
            ->whereNull('da.deleted_at')
            ->whereNull('u.deleted_at')
            ->pluck('da.delivery_agent_id', 'u.name');

        $results = [
            'imported'          => 0,
            'skipped'           => 0,
            'errors'            => [],
            'created_companies' => [],
        ];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // row 1 = headers

            [$dto, $hint, $rawStatus] = $this->buildDto($row->toArray(), $rowNumber);

            $errors = $this->validator->validate($dto, $hint, $rawStatus);
            if ($errors !== []) {
                $results['errors'] = array_merge($results['errors'], $errors);
                $results['skipped']++;
                foreach ($errors as $error) {
                    Log::channel('daily')->warning("OrderImport row validation failed: {$error}", [
                        'row' => $rowNumber,
                        'dto' => (array) $dto,
                    ]);
                }
                continue;
            }

            $governorateId = $governorateCache->get($dto->governorateName);
            if (! $governorateId) {
                $errMsg = "Row {$rowNumber}: محافظة غير معروفة: {$dto->governorateName}";
                $results['errors'][] = $errMsg;
                $results['skipped']++;
                Log::channel('daily')->warning('OrderImport governorate resolution failed', [
                    'row'         => $rowNumber,
                    'governorate' => $dto->governorateName,
                ]);
                continue;
            }

            $shippingCompanyId = $this->resolveOrCreateCompany(
                dto:          $dto,
                companyCache: $companyCache,
                results:      $results,
            );

            [$deliveryAgentId, $assignedAt] = $this->resolveAgent(
                dto:        $dto,
                hint:       $hint,
                agentCache: $agentCache,
                rowNumber:  $rowNumber,
                results:    $results,
            );

            if ($deliveryAgentId === false) {
                continue;
            }

            // Resolve company name for reference_code generation (before transaction).
            $companyNameForCode = $dto->companyName;

            try {
                DB::transaction(function () use ($dto, $hint, $shippingCompanyId, $governorateId, $rowNumber, $deliveryAgentId, $assignedAt, $companyNameForCode) {
                    $orderId = (string) Str::uuid();
                    $now     = now();

                    // Use الكود from sheet as reference_no; fall back to row number.
                    $referenceNo   = $dto->referenceNo !== ''
                        ? $dto->referenceNo
                        : 'R' . $rowNumber;

                    // Generate the display reference_code from company name + excel code + date.
                    $referenceCode = $this->referenceCodeGenerator->generate(
                        companyName:    $companyNameForCode,
                        excelOrderCode: $dto->referenceNo,
                        date:           $now,
                    );

                    $hasCollection = $hint?->hasCollection() ?? false;
                    $isTerminal    = $hint?->isTerminal() ?? false;

                    // ── orders ─────────────────────────────────────────────
                    DB::table('orders')->insert([
                        'order_id'             => $orderId,
                        'reference_no'         => $referenceNo,
                        'reference_code'       => $referenceCode,
                        'shipping_company_id'  => $shippingCompanyId,
                        'delivery_agent_id'    => $deliveryAgentId,
                        'status'               => $dto->statusId,
                        'notes'                => $dto->itemDescription ?: null,
                        'display_company_name' => $dto->displayCompanyName ?: null,
                        'assigned_at'          => $assignedAt,
                        'delivered_at'         => $hasCollection ? $now : null,
                        'created_at'           => $now,
                        'updated_at'           => $now,
                    ]);

                    // ── order_customer_info ────────────────────────────────
                    [$customerPhone, $phoneAlt] = $this->splitPhones($dto->customerPhones);

                    DB::table('order_customer_info')->insert([
                        'order_customer_info_id' => (string) Str::uuid(),
                        'order_id'               => $orderId,
                        'customer_name'          => $dto->customerName,
                        'customer_phone'         => $customerPhone,
                        'phone_alt'              => $phoneAlt,
                        'created_at'             => $now,
                        'updated_at'             => $now,
                    ]);

                    // ── order_addresses ────────────────────────────────────
                    DB::table('order_addresses')->insert([
                        'order_address_id' => (string) Str::uuid(),
                        'order_id'         => $orderId,
                        'governorate_id'   => $governorateId,
                        'city_id'          => null,
                        'address_line'     => $dto->addressLine,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);

                    // ── order_financials ───────────────────────────────────
                    [
                        'original_amount'  => $originalAmount,
                        'approved_amount'  => $approvedAmount,
                        'collected_amount' => $collectedAmount,
                        'shipping_fee'     => $shippingFee,
                    ] = $this->buildFinancialFields($dto, $hint);

                    DB::table('order_financials')->insert([
                        'order_financial_id' => (string) Str::uuid(),
                        'order_id'           => $orderId,
                        'original_amount'    => $originalAmount,
                        'approved_amount'    => $approvedAmount,
                        'collected_amount'   => $collectedAmount,
                        'shipping_fee'       => $shippingFee,
                        'commission_amount'  => null, // computed at settlement time
                        'net_due_company'    => null, // computed at settlement time
                        'is_settled'         => false,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ]);

                    // ── order_items ────────────────────────────────────────
                    DB::table('order_items')->insert([
                        'order_item_id'      => (string) Str::uuid(),
                        'order_id'           => $orderId,
                        'item_description'   => null,
                        'total_quantity'     => $dto->quantity,
                        'delivered_quantity' => $this->resolveDeliveredQuantity($hint, $dto->quantity),
                        'returned_quantity'  => null,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ]);

                    // ── order_schedules ────────────────────────────────────
                    DB::table('order_schedules')->insert([
                        'order_schedule_id'      => (string) Str::uuid(),
                        'order_id'               => $orderId,
                        'expected_delivery_date' => null,
                        'postponed_date'         => null,
                        'schedule_notes'         => null,
                        'created_at'             => $now,
                        'updated_at'             => $now,
                    ]);

                    // ── order_approvals ────────────────────────────────────
                    // Terminal imported orders do not need pending approval.
                    DB::table('order_approvals')->insert([
                        'order_approval_id' => (string) Str::uuid(),
                        'order_id'          => $orderId,
                        'requires_approval' => ! $isTerminal,
                        'approval_granted'  => $isTerminal ? 1 : null,
                        'approved_by'       => null,
                        'approved_at'       => $isTerminal ? $now : null,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ]);

                    // ── order_status_history ───────────────────────────────
                    DB::table('order_status_history')->insert([
                        'order_status_history_id' => (string) Str::uuid(),
                        'order_id'                => $orderId,
                        'from_status_id'          => null,
                        'to_status_id'            => $dto->statusId,
                        'changed_by'              => null,
                        'notes'                   => 'مستورد من ملف Excel',
                        'created_at'              => $now,
                        'updated_at'              => $now,
                    ]);
                });

                $results['imported']++;

            } catch (\Throwable $e) {
                $errMsg = "Row {$rowNumber}: فشل الحفظ — {$e->getMessage()}";
                $results['errors'][] = $errMsg;
                $results['skipped']++;
                Log::channel('daily')->error('OrderImport save failed', [
                    'row'       => $rowNumber,
                    'error'     => $e->getMessage(),
                    'exception' => $e,
                ]);
            }
        }

        return $results;
    }

    /**
     * Parse one raw Excel row into a DTO + resolved hint enum.
     *
     * New column layout (0-based index, RTL Arabic sheet):
     *  [0]  الكود          → reference_no
     *  [1]  اسم العميل     → customer_name
     *  [2]  العنوان        → address_line
     *  [3]  المحافظة       → governorate_id
     *  [4]  رقم التليفون   → customer_phone / phone_alt
     *  [5]  وصف الشحنة     → orders.notes (order-level description)
     *  [6]  عدد القطع      → total_quantity
     *  [7]  الإجمالي       → original_amount
     *  [8]  اسم الشركة     → display only (stored in status history note)
     *  [9]  اسم الراسل     → shipping_company lookup / auto-create
     *  [10] اسم المندوب    → delivery_agent lookup
     *  [11] موقف العميل    → status (empty = pending / 1)
     *
     * @return array{0: ImportOrderRowDTO, 1: ?ImportStatusHintEnum, 2: string}
     */
    private function buildDto(array $row, int $rowNumber): array
    {
        $rawStatus = isset($row[11]) ? trim((string) $row[11]) : '';
        $hint      = ImportStatusHintEnum::fromArabic($rawStatus);
        $statusId  = $hint?->toStatusId() ?? 1;

        $dto = new ImportOrderRowDTO(
            referenceNo:         trim((string) ($row[0] ?? '')),
            customerName:        trim((string) ($row[1] ?? '')),
            customerPhones:      $this->normalizePhones((string) ($row[4] ?? '')),
            addressLine:         trim((string) ($row[2] ?? '')),
            governorateName:     trim((string) ($row[3] ?? '')),
            itemDescription:     trim((string) ($row[5] ?? '')),
            quantity:            max(1, (int) ($row[6] ?? 1)),
            codAmount:           max(0.0, (float) ($row[7] ?? 0)),
            displayCompanyName:  trim((string) ($row[8] ?? '')),
            companyName:         trim((string) ($row[9] ?? '')),
            agentName:           trim((string) ($row[10] ?? '')),
            statusId:            $statusId,
            rowNumber:           $rowNumber,
        );

        return [$dto, $hint, $rawStatus];
    }

    /**
     * Resolve delivery agent by user name (column 2 — مندوب الشحن).
     *
     * @return array{0: ?string, 1: ?\Illuminate\Support\Carbon}|false
     *         false = row should be skipped (error already logged)
     */
    private function resolveAgent(
        ImportOrderRowDTO $dto,
        ?ImportStatusHintEnum $hint,
        Collection $agentCache,
        int $rowNumber,
        array &$results,
    ): array|false {
        if ($dto->agentName === '') {
            return [null, null];
        }

        $deliveryAgentId = $agentCache->get($dto->agentName);

        if (! $deliveryAgentId) {
            $errMsg = "Row {$rowNumber}: مندوب غير معروف: {$dto->agentName}";
            $results['errors'][] = $errMsg;
            $results['skipped']++;
            Log::channel('daily')->warning('OrderImport agent resolution failed', [
                'row'        => $rowNumber,
                'agent_name' => $dto->agentName,
            ]);

            return false;
        }

        return [$deliveryAgentId, now()];
    }

    private function resolveOrCreateCompany(
        ImportOrderRowDTO $dto,
        Collection &$companyCache,
        array &$results,
    ): string {
        if ($companyCache->has($dto->companyName)) {
            return $companyCache->get($dto->companyName);
        }

        $shippingCompanyId = null;

        DB::transaction(function () use ($dto, &$shippingCompanyId, &$companyCache, &$results) {
            $userId = (string) Str::uuid();
            // No phone column in new sheet — generate a placeholder.
            $phone  = $this->resolveUniquePhone('', $userId);
            $email  = $this->resolveUniqueEmail($dto->companyName, $userId);

            $user = User::query()->create([
                'user_id'      => $userId,
                'name'         => $dto->companyName,
                'email'        => $email,
                'phone'        => $phone,
                'password'     => Hash::make(Str::random(32)),
                'account_type' => AccountTypeEnum::ShippingCompany->value,
                'is_active'    => true,
            ]);

            $user->assignRole('shipping_company');

            $company = ShippingCompany::query()->create([
                'user_id'          => $user->user_id,
                'company_name'     => $dto->companyName,
                'commission_type'  => 1,   // percentage — admin configures later
                'commission_value' => 0,
                'balance'          => 0,
                'is_active'        => 1,
            ]);

            $shippingCompanyId = $company->shipping_company_id;
            $companyCache->put($dto->companyName, $shippingCompanyId);

            $results['created_companies'][] = [
                'name' => $dto->companyName,
                'id'   => $shippingCompanyId,
            ];
        });

        if ($shippingCompanyId === null) {
            throw new \RuntimeException("Failed to create company record for: {$dto->companyName}");
        }

        return $shippingCompanyId;
    }

    /**
     * Map Excel الاجمالي to order_financials fields per BRD §3 delivery scenarios.
     *
     * @return array{original_amount: float, approved_amount: ?float, collected_amount: ?float, shipping_fee: ?float}
     */
    private function buildFinancialFields(ImportOrderRowDTO $dto, ?ImportStatusHintEnum $hint): array
    {
        $originalAmount  = $dto->codAmount;
        $approvedAmount  = null;
        $collectedAmount = null;
        $shippingFee     = null;

        if (! $hint?->hasCollection()) {
            return [
                'original_amount'  => $originalAmount,
                'approved_amount'  => null,
                'collected_amount' => null,
                'shipping_fee'     => null,
            ];
        }

        $collectedAmount = $dto->codAmount;

        if ($hint === ImportStatusHintEnum::DeliveredPriceChanged) {
            $approvedAmount = $dto->codAmount;
        }

        if ($hint === ImportStatusHintEnum::RefusedPaidShipping) {
            $shippingFee    = $dto->codAmount;
            $originalAmount = 0;
        }

        return [
            'original_amount'  => $originalAmount,
            'approved_amount'  => $approvedAmount,
            'collected_amount' => $collectedAmount,
            'shipping_fee'     => $shippingFee,
        ];
    }

    private function resolveDeliveredQuantity(?ImportStatusHintEnum $hint, int $quantity): ?int
    {
        return match ($hint) {
            ImportStatusHintEnum::Delivered,
            ImportStatusHintEnum::DeliveredPriceChanged => $quantity,
            ImportStatusHintEnum::RefusedPaidShipping     => 0,
            ImportStatusHintEnum::PartialDelivery         => null,
            default                                       => null,
        };
    }

    // ── Phone helpers ──────────────────────────────────────────────────────

    /** @return array{0: string, 1: string|null} */
    private function splitPhones(array $phones): array
    {
        return [$phones[0] ?? '', $phones[1] ?? null];
    }

    private function resolveUniqueEmail(string $companyName, string $userId): string
    {
        $slug  = Str::slug($companyName) ?: 'company';
        $email = "{$slug}.{$userId}@import.marsal.local";

        while (User::query()->where('email', $email)->exists()) {
            $email = "{$slug}." . Str::random(8) . '@import.marsal.local';
        }

        return $email;
    }

    private function resolveUniquePhone(string $preferred, string $userId): string
    {
        $phone = $preferred !== '' ? $preferred : $this->phoneFromUuid($userId);

        while (User::query()->where('phone', $phone)->exists()) {
            $phone = '010' . random_int(10000000, 99999999);
        }

        return $phone;
    }

    private function phoneFromUuid(string $uuid): string
    {
        $digits = preg_replace('/\D/', '', $uuid) ?: (string) random_int(10000000, 99999999);

        return '010' . substr(str_pad($digits, 8, '0'), 0, 8);
    }

    private function normalizePhone(string $raw): string
    {
        $digits = strtr($raw, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $digits = preg_replace('/\D/', '', $digits);

        // Egyptian mobile: 10-digit starting with 1 → prepend 0
        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    /** @return string[] */
    private function normalizePhones(string $raw): array
    {
        $parts  = preg_split('/[\/\-,\s]+/', $raw);
        $phones = [];

        foreach ($parts as $part) {
            $phone = $this->normalizePhone($part);
            if ($phone !== '') {
                $phones[] = $phone;
            }
        }

        return $phones;
    }
}
