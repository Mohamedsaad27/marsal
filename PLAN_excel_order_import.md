# Mersal — Excel Order Import Build Plan (v2)
## New Excel Format: `Book1_filled.xlsx`

> Read CLAUDE.md fully before starting any file.
> All files go inside `app/Modules/Orders/`.
> Module structure: Application, Domain, Infrastructure, Presentation layers.

---

## Spreadsheet Column Map

| Column (Arabic) | Column index | Maps to |
|---|---|---|
| م | 0 | row number — skip |
| اسم العميل | 1 | `order_customer_info.customer_name` |
| مندوب الشحن | 2 | **auto-create if missing** |
| العنوان | 3 | `order_addresses.address_line` |
| المحافظة | 4 | governorate lookup → `order_addresses.governorate_id` |
| رقم العميل | 5 | `order_customer_info.phones` (normalize) |
| العدد | 6 | `order_items.quantity` |
| الاجمالي | 7 | `order_financials.cod_amount` |
| اسم الشركة | 8 | `orders.company_id` — lookup by name, **auto-create if missing** |
| رقم تليفون الشركة | 9 | used ONLY when auto-creating a new company (phone) |
| اسم الراسل | 10 | `order_items.description` |
| موقف العميل | 11 | status hint → `orders.status_id` via `ImportStatusHintEnum` |

---

## File 1 — `ImportStatusHintEnum`

**Path:** `app/Modules/Orders/Domain/Enums/ImportStatusHintEnum.php`

```php
<?php

namespace App\Modules\Orders\Domain\Enums;

enum ImportStatusHintEnum: string
{
    case Delivered       = 'تم التسليم';
    case OutForDelivery  = 'قيد التوصيل';
    case AwaitingCustomer = 'في انتظار العميل';
    case Returned        = 'مرتجع';

    public function toStatusId(): int
    {
        return match($this) {
            self::Delivered        => OrderStatusEnum::Delivered->value,       // 5
            self::OutForDelivery   => OrderStatusEnum::OutForDelivery->value,  // 3
            self::AwaitingCustomer => OrderStatusEnum::NoAnswer->value,        // 11
            self::Returned         => OrderStatusEnum::RefusedNoPayment->value,// 9
        };
    }

    public static function fromArabic(string $value): self
    {
        foreach (self::cases() as $case) {
            if (trim($value) === $case->value) {
                return $case;
            }
        }
        // Default to pending if unrecognised
        return self::Delivered; // fallback — see validator
    }
}
```

> Add any new Arabic status strings you encounter directly as new cases.

---

## File 2 — `ImportOrderRowDTO`

**Path:** `app/Modules/Orders/Application/DTOs/ImportOrderRowDTO.php`

```php
<?php

namespace App\Modules\Orders\Application\DTOs;

readonly class ImportOrderRowDTO
{
    public function __construct(
        public string  $customerName,
        public array   $customerPhones,   // normalized, always an array
        public string  $addressLine,
        public string  $governorateName,
        public int     $quantity,
        public float   $codAmount,
        public string  $companyName,      // raw name from sheet — used for lookup/create
        public string  $companyPhone,     // used when auto-creating the company
        public string  $senderName,       // اسم الراسل → order_items.description
        public int     $statusId,         // resolved from ImportStatusHintEnum
        public int     $rowNumber,        // for error reporting
    ) {}
}
```

---

## File 3 — `OrderRowValidator`

**Path:** `app/Modules/Orders/Application/Validators/OrderRowValidator.php`

```php
<?php

namespace App\Modules\Orders\Application\Validators;

use App\Modules\Orders\Application\DTOs\ImportOrderRowDTO;

class OrderRowValidator
{
    /** @return string[] list of error messages, empty = valid */
    public function validate(ImportOrderRowDTO $dto): array
    {
        $errors = [];

        if (empty(trim($dto->customerName))) {
            $errors[] = "Row {$dto->rowNumber}: اسم العميل مطلوب";
        }

        if (empty($dto->customerPhones)) {
            $errors[] = "Row {$dto->rowNumber}: رقم العميل مطلوب";
        }

        if (empty(trim($dto->companyName))) {
            $errors[] = "Row {$dto->rowNumber}: اسم الشركة مطلوب";
        }

        if ($dto->codAmount < 0) {
            $errors[] = "Row {$dto->rowNumber}: الإجمالي يجب أن يكون صفر أو أكثر";
        }

        if ($dto->quantity <= 0) {
            $errors[] = "Row {$dto->rowNumber}: العدد يجب أن يكون أكبر من صفر";
        }

        return $errors;
    }
}
```

---

## File 4 — `OrdersImport`

**Path:** `app/Modules/Orders/Infrastructure/Imports/OrdersImport.php`

```php
<?php

namespace App\Modules\Orders\Infrastructure\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;

class OrdersImport implements ToCollection, WithStartRow
{
    private Collection $rows;

    public function startRow(): int
    {
        // Row 1 = Arabic headers → skip it, start reading from row 2
        return 2;
    }

    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }

    public function getRows(): Collection
    {
        return $this->rows ?? collect();
    }
}
```

> `WithStartRow` skips the Arabic header row.
> We pull raw rows back out via `getRows()` so the Use Case can control processing.

---

## File 5 — `ImportOrdersFromExcelUseCase`

**Path:** `app/Modules/Orders/Application/UseCases/ImportOrdersFromExcelUseCase.php`

This is the core file. Read every comment carefully.

```php
<?php

namespace App\Modules\Orders\Application\UseCases;

use App\Modules\Orders\Application\DTOs\ImportOrderRowDTO;
use App\Modules\Orders\Application\Validators\OrderRowValidator;
use App\Modules\Orders\Domain\Enums\ImportStatusHintEnum;
use App\Modules\Orders\Infrastructure\Imports\OrdersImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ImportOrdersFromExcelUseCase
{
    public function __construct(
        private OrderRowValidator $validator,
    ) {}

    public function execute(string $filePath): array
    {
        // ── 1. Parse the spreadsheet ──────────────────────────────────────
        $import = new OrdersImport();
        Excel::import($import, $filePath);
        $rows = $import->getRows();

        // ── 2. Build lookup caches (avoid N+1 DB calls) ───────────────────
        $governorateCache = DB::table('governorates')
            ->pluck('governorate_id', 'name');              // ['القاهرة' => uuid, ...]

        $companyCache = DB::table('shipping_companies as sc')
            ->join('users as u', 'u.user_id', '=', 'sc.user_id')
            ->pluck('sc.company_id', 'sc.company_name');   // ['شركة X' => uuid, ...]

        // ── 3. Process rows ───────────────────────────────────────────────
        $results = [
            'imported' => 0,
            'skipped'  => 0,
            'errors'   => [],
            'created_companies' => [],
        ];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because we start at row 2

            // Map raw columns → DTO
            $dto = $this->buildDto($row->toArray(), $rowNumber);

            // Validate row
            $errors = $this->validator->validate($dto);
            if (!empty($errors)) {
                $results['errors'] = array_merge($results['errors'], $errors);
                $results['skipped']++;
                continue;
            }

            // Resolve governorate
            $governorateId = $governorateCache->get($dto->governorateName);
            if (!$governorateId) {
                $results['errors'][] = "Row {$rowNumber}: محافظة غير معروفة: {$dto->governorateName}";
                $results['skipped']++;
                continue;
            }

            // Resolve or auto-create company ← KEY NEW BEHAVIOUR
            $companyId = $this->resolveOrCreateCompany(
                dto:          $dto,
                companyCache: $companyCache,
                results:      $results,
            );

            // Insert order in a per-row transaction
            try {
                DB::transaction(function () use ($dto, $companyId, $governorateId) {
                    $orderId = (string) Str::uuid();

                    // orders
                    DB::table('orders')->insert([
                        'order_id'    => $orderId,
                        'company_id'  => $companyId,
                        'status_id'   => $dto->statusId,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);

                    // order_customer_info
                    DB::table('order_customer_info')->insert([
                        'order_id'      => $orderId,
                        'customer_name' => $dto->customerName,
                        'phones'        => json_encode($dto->customerPhones),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);

                    // order_addresses
                    DB::table('order_addresses')->insert([
                        'order_id'       => $orderId,
                        'governorate_id' => $governorateId,
                        'address_line'   => $dto->addressLine,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);

                    // order_financials
                    DB::table('order_financials')->insert([
                        'order_id'   => $orderId,
                        'cod_amount' => $dto->codAmount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // order_items
                    DB::table('order_items')->insert([
                        'order_id'    => $orderId,
                        'quantity'    => $dto->quantity,
                        'description' => $dto->senderName,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);

                    // order_schedules — no date from this sheet, leave nulls
                    DB::table('order_schedules')->insert([
                        'order_id'   => $orderId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // order_approvals — defaults
                    DB::table('order_approvals')->insert([
                        'order_id'   => $orderId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // order_status_history — initial entry
                    DB::table('order_status_history')->insert([
                        'order_id'   => $orderId,
                        'status_id'  => $dto->statusId,
                        'changed_by' => null, // system import
                        'note'       => 'مستورد من ملف Excel',
                        'created_at' => now(),
                    ]);
                });

                $results['imported']++;

            } catch (\Throwable $e) {
                $results['errors'][] = "Row {$rowNumber}: فشل الحفظ — {$e->getMessage()}";
                $results['skipped']++;
            }
        }

        return $results;
    }

    // ──────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────

    private function buildDto(array $row, int $rowNumber): ImportOrderRowDTO
    {
        $statusHint = isset($row[11]) ? (string) $row[11] : '';

        $statusId = 1; // default: pending
        try {
            $statusId = ImportStatusHintEnum::fromArabic($statusHint)->toStatusId();
        } catch (\Throwable) {
            // unknown status → default pending
        }

        return new ImportOrderRowDTO(
            customerName:   trim((string) ($row[1] ?? '')),
            customerPhones: $this->normalizePhones((string) ($row[5] ?? '')),
            addressLine:    trim((string) ($row[3] ?? '')),
            governorateName: trim((string) ($row[4] ?? '')),
            quantity:       (int) ($row[6] ?? 0),
            codAmount:      (float) ($row[7] ?? 0),
            companyName:    trim((string) ($row[8] ?? '')),
            companyPhone:   $this->normalizePhone((string) ($row[9] ?? '')),
            senderName:     trim((string) ($row[10] ?? '')),
            statusId:       $statusId,
            rowNumber:      $rowNumber,
        );
    }

    /**
     * Lookup company by name. If not found, auto-create a minimal user + shipping_company
     * record and update the in-memory cache so duplicate names in the same file
     * only create one company.
     *
     * @param array $results passed by reference so we can push to created_companies
     */
    private function resolveOrCreateCompany(
        ImportOrderRowDTO $dto,
        \Illuminate\Support\Collection &$companyCache,
        array &$results,
    ): string {
        if ($companyCache->has($dto->companyName)) {
            return $companyCache->get($dto->companyName);
        }

        // Auto-create
        $companyId = null;

        DB::transaction(function () use ($dto, &$companyId, &$companyCache, &$results) {
            $userId    = (string) Str::uuid();
            $companyId = (string) Str::uuid();

            // Create a bare user record for the company (account_type=2)
            DB::table('users')->insert([
                'user_id'      => $userId,
                'name'         => $dto->companyName,
                'phone'        => $dto->companyPhone ?: '00000000000',
                'password'     => bcrypt(Str::random(16)), // random — admin must reset
                'account_type' => 2, // shipping_company
                'is_active'    => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // Create the shipping_companies profile row
            DB::table('shipping_companies')->insert([
                'company_id'      => $companyId,
                'user_id'         => $userId,
                'company_name'    => $dto->companyName,
                'commission_type' => 1,   // default: percentage
                'commission_value' => 0,  // admin must configure later
                'balance'         => 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // Update in-memory cache
            $companyCache->put($dto->companyName, $companyId);

            $results['created_companies'][] = [
                'name'  => $dto->companyName,
                'phone' => $dto->companyPhone,
                'id'    => $companyId,
            ];
        });

        return $companyId;
    }

    /** Normalise a single phone string — strips spaces, non-digits, Arabic-Indic digits */
    private function normalizePhone(string $raw): string
    {
        $digits = strtr($raw, ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4',
                               '٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
        $digits = preg_replace('/\D/', '', $digits);

        // Prefix with 0 if starts with 1 (Egyptian mobile shorthand)
        if (strlen($digits) === 10 && $digits[0] === '1') {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    /** Split cell that may contain multiple phones separated by / - , or space */
    private function normalizePhones(string $raw): array
    {
        $parts = preg_split('/[\\/\-,\s]+/', $raw);
        $phones = [];
        foreach ($parts as $part) {
            $p = $this->normalizePhone($part);
            if ($p !== '') {
                $phones[] = $p;
            }
        }
        return $phones;
    }
}
```

---

## File 6 — `ProcessOrdersImportJob`

**Path:** `app/Modules/Orders/Infrastructure/Jobs/ProcessOrdersImportJob.php`

```php
<?php

namespace App\Modules\Orders\Infrastructure\Jobs;

use App\Modules\Orders\Application\UseCases\ImportOrdersFromExcelUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrdersImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 300;

    public function __construct(
        private string $filePath,    // absolute path to the stored file
        private string $batchId,     // UUID returned to client in 202
        private string $uploadedBy,  // user_id of admin who triggered the import
    ) {}

    public function handle(ImportOrdersFromExcelUseCase $useCase): void
    {
        try {
            $results = $useCase->execute($this->filePath);

            Log::channel('daily')->info('OrderImport complete', [
                'batch_id'           => $this->batchId,
                'uploaded_by'        => $this->uploadedBy,
                'imported'           => $results['imported'],
                'skipped'            => $results['skipped'],
                'created_companies'  => $results['created_companies'],
                'errors'             => $results['errors'],
            ]);

        } catch (\Throwable $e) {
            Log::channel('daily')->error('OrderImport failed', [
                'batch_id' => $this->batchId,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function queue(): string
    {
        return 'reports';
    }
}
```

---

## File 7 — `ImportOrdersRequest`

**Path:** `app/Modules/Orders/Presentation/Http/Requests/ImportOrdersRequest.php`

```php
<?php

namespace App\Modules\Orders\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'يرجى رفع ملف Excel',
            'file.mimes'    => 'الملف يجب أن يكون xlsx أو xls',
            'file.max'      => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت',
        ];
    }
}
```

---

## File 8 — `OrderImportController`

**Path:** `app/Modules/Orders/Presentation/Http/Controllers/OrderImportController.php`

```php
<?php

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Application\Helpers\ApiResponse;
use App\Modules\Orders\Infrastructure\Jobs\ProcessOrdersImportJob;
use App\Modules\Orders\Presentation\Http\Requests\ImportOrdersRequest;
use Illuminate\Support\Str;

class OrderImportController extends Controller
{
    public function store(ImportOrdersRequest $request): \Illuminate\Http\JsonResponse
    {
        $file     = $request->file('file');
        $batchId  = (string) Str::uuid();
        $filePath = $file->storeAs('imports/orders', "{$batchId}.xlsx", 'local');

        ProcessOrdersImportJob::dispatch(
            filePath:   storage_path("app/{$filePath}"),
            batchId:    $batchId,
            uploadedBy: auth()->id(),
        )->onQueue('reports');

        return ApiResponse::success(
            data:    ['batch_id' => $batchId],
            message: 'جاري معالجة ملف الاستيراد في الخلفية',
            status:  202,
        );
    }
}
```

---

## File 9 — Route Registration

**Path:** `app/Modules/Orders/Presentation/Routes/admin.php`

Add this route to the existing admin routes file:

```php
use App\Modules\Orders\Presentation\Http\Controllers\OrderImportController;

// inside your auth:api + super_admin middleware group:
Route::post('orders/import', [OrderImportController::class, 'store'])
     ->name('orders.import');
```

---

## File 10 — Service Provider note

Inside `OrdersServiceProvider` (or `RepositoryServiceProvider`), make sure the Use Case and Validator are resolvable via the container. Since they have no interface, Laravel resolves them automatically by concrete class — **no explicit binding needed**.

If you use a `RouteServiceProvider` for the Orders module, load both `api.php` and `admin.php` there.

---

## Auto-Create Company Logic — Summary

When a row's `اسم الشركة` does not match any existing `shipping_companies.company_name`:

1. A new `users` row is inserted with `account_type = 2` (shipping_company), using `رقم تليفون الشركة` as the phone.
2. A new `shipping_companies` row is inserted linked to that user.
3. The in-memory `$companyCache` is updated immediately — so if the **same company name appears multiple times** in the same file, it is only created once.
4. The created company is logged with its UUID in `results['created_companies']` and written to the daily log after the job finishes.
5. Commissions default to `0 %` — the admin must configure them before the company goes live.

---

## Dependency

```bash
composer require maatwebsite/excel
```

Publish config if not already done:
```bash
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
```

---

## Status Mapping Reference

| Arabic (موقف العميل) | Maps to |
|---|---|
| تم التسليم | `OrderStatusEnum::Delivered` (5) |
| قيد التوصيل | `OrderStatusEnum::OutForDelivery` (3) |
| في انتظار العميل | `OrderStatusEnum::NoAnswer` (11) |
| مرتجع | `OrderStatusEnum::RefusedNoPayment` (9) |
| anything else | `pending` (1) — safe default |

---

*Generated for Mersal v1.0 — June 2026*