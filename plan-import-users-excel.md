# Plan: Import Users from Excel — Mersal

> Feature: Admin uploads an `.xlsx` / `.csv` file from the Users dashboard ("استيراد من Excel" button).
> Each row creates a user + assigns the correct Spatie role + creates the linked profile record.
> Invalid rows are **never silently dropped** — a structured error report is returned to the admin.

---

## 1. Overview & Design Decisions

| Decision | Choice | Reason |
|---|---|---|
| Processing mode | **Synchronous** (no queue) for ≤ 500 rows | Simple; instant feedback for admin |
| Row failure policy | **Skip-and-collect** — valid rows are saved, invalid rows return a per-row error report | Partial import is more useful than all-or-nothing at this scale |
| Duplicate detection | Email + Phone uniqueness checked against live DB before insert | `UNIQUE` constraints on both columns |
| Password | Auto-generated (12 chars) per row, returned in the response so admin can share | No email flow needed |
| Supported roles in file | `delivery_agent`, `shipping_company` only | `super_admin` rows are rejected — too sensitive to import in bulk |
| Transaction | One `DB::transaction()` per valid row — not a single outer transaction | Prevents one bad row rolling back an entire 400-row file |
| Library | `maatwebsite/excel` (already installed) — `ToCollection` concern | Gives full collection control without auto-persist magic |

---

## 2. Excel File Format

The admin downloads a template and fills it. Columns (order matters):

| Column | Header (Arabic) | Header (English key) | Required | Notes |
|---|---|---|---|---|
| A | الاسم | `name` | ✅ | max 255 |
| B | البريد الإلكتروني | `email` | ✅ | unique, valid email |
| C | الهاتف | `phone` | ✅ | unique, max 20 chars |
| D | الجنس | `gender` | ❌ | `ذكر` / `أنثى` — nullable |
| E | الدور | `role` | ✅ | `delivery_agent` or `shipping_company` |
| F | اسم الشركة | `company_name` | conditional | required when role = `shipping_company` |

Row 1 = header (skipped). Data starts row 2.

---

## 3. Files to Create / Edit

| File | Action |
|---|---|
| `app/Modules/Users/Application/DTOs/ImportUserRowDTO.php` | **New** |
| `app/Modules/Users/Application/DTOs/ImportUsersResultDTO.php` | **New** |
| `app/Modules/Users/Application/Exceptions/UnsupportedRoleImportException.php` | **New** |
| `app/Modules/Users/Infrastructure/Excel/UsersImport.php` | **New** (Maatwebsite concern) |
| `app/Modules/Users/Application/UseCases/ImportUsersUseCase.php` | **New** |
| `app/Modules/Users/Presentation/Http/Requests/ImportUsersRequest.php` | **New** |
| `app/Modules/Users/Presentation/Http/Resources/ImportUsersResultResource.php` | **New** |
| `app/Modules/Users/Domain/Interfaces/UserRepositoryInterface.php` | Add `createUserWithRole()` |
| `app/Modules/Users/Infrastructure/Persistence/UserRepository.php` | Implement `createUserWithRole()` |
| `app/Modules/Users/Presentation/Http/Controllers/AdminUserController.php` | Add `import()` method |
| `app/Modules/Users/Presentation/Routes/api.php` | Add `POST admin/users/import` route |
| `app/Modules/Users/Presentation/Resources/lang/ar/users.php` | Add strings |
| `app/Modules/Users/Presentation/Resources/lang/en/users.php` | Add strings |

**Total: 13 touches — 8 new files, 5 edits.**

---

## 4. Form Request

**File:** `app/Modules/Users/Presentation/Http/Requests/ImportUsersRequest.php`

```php
<?php

namespace App\Modules\Users\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission gate handled in controller
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:5120', // 5 MB ceiling
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => __('users.import_file_required'),
            'file.mimes'    => __('users.import_file_invalid_type'),
            'file.max'      => __('users.import_file_too_large'),
        ];
    }
}
```

---

## 5. DTOs

### 5.1 Row DTO

**File:** `app/Modules/Users/Application/DTOs/ImportUserRowDTO.php`

```php
<?php

namespace App\Modules\Users\Application\DTOs;

readonly class ImportUserRowDTO
{
    public function __construct(
        public string  $name,
        public string  $email,
        public string  $phone,
        public ?string $gender,
        public string  $role,        // 'delivery_agent' | 'shipping_company'
        public ?string $companyName, // required when role = shipping_company
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            name:        trim((string) ($row['name']         ?? '')),
            email:       strtolower(trim((string) ($row['email'] ?? ''))),
            phone:       trim((string) ($row['phone']        ?? '')),
            gender:      filled($row['gender'] ?? null) ? trim((string) $row['gender']) : null,
            role:        strtolower(trim((string) ($row['role']  ?? ''))),
            companyName: filled($row['company_name'] ?? null) ? trim((string) $row['company_name']) : null,
        );
    }
}
```

### 5.2 Result DTO

**File:** `app/Modules/Users/Application/DTOs/ImportUsersResultDTO.php`

```php
<?php

namespace App\Modules\Users\Application\DTOs;

readonly class ImportUsersResultDTO
{
    public function __construct(
        public int   $totalRows,
        public int   $importedCount,
        public int   $failedCount,
        public array $imported, // array of ['row' => int, 'name' => string, 'email' => string, 'generated_password' => string]
        public array $errors,   // array of ['row' => int, 'email' => string|null, 'errors' => string[]]
    ) {}
}
```

---

## 6. Maatwebsite Import Concern

**File:** `app/Modules/Users/Infrastructure/Excel/UsersImport.php`

```php
<?php

namespace App\Modules\Users\Infrastructure\Excel;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use Illuminate\Support\Collection;

class UsersImport implements ToCollection, WithHeadingRow, WithLimit
{
    private Collection $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    /**
     * WithLimit: reject files over 500 data rows before processing.
     */
    public function limit(): int
    {
        return 500;
    }

    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }

    public function getRows(): Collection
    {
        return $this->rows;
    }
}
```

> `WithHeadingRow` maps the first row as keys — so `$row['name']`, `$row['email']` etc. work directly.
> `WithLimit` stops reading after 500 rows — protects memory; admin sees a validation error if file is too large.

---

## 7. Repository Interface — add one method

**File:** `app/Modules/Users/Domain/Interfaces/UserRepositoryInterface.php`

```php
use App\Modules\Users\Application\DTOs\ImportUserRowDTO;

/**
 * Creates a user, assigns their Spatie role, and creates their linked profile
 * (delivery_agent or shipping_company record) — all inside one DB transaction.
 *
 * @return array{user_id: string, generated_password: string}
 */
public function createUserWithRole(ImportUserRowDTO $dto, string $plainPassword): array;
```

---

## 8. Repository Implementation

**File:** `app/Modules/Users/Infrastructure/Persistence/UserRepository.php`

```php
use App\Modules\Users\Application\DTOs\ImportUserRowDTO;
use App\Modules\Users\Infrastructure\Database\Models\User;
use App\Modules\DeliveryAgents\Infrastructure\Database\Models\DeliveryAgent;
use App\Modules\ShippingCompanies\Infrastructure\Database\Models\ShippingCompany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

public function createUserWithRole(ImportUserRowDTO $dto, string $plainPassword): array
{
    return DB::transaction(function () use ($dto, $plainPassword) {

        $userId = Str::uuid()->toString();

        $user = User::create([
            'user_id'    => $userId,
            'name'       => $dto->name,
            'email'      => $dto->email,
            'phone'      => $dto->phone,
            'gender'     => $dto->gender,
            'password'   => Hash::make($plainPassword),
            'is_active'  => 1,
        ]);

        // Assign Spatie role
        $role = Role::findByName($dto->role, 'api');
        $user->assignRole($role);

        // Create the linked profile record
        if ($dto->role === 'delivery_agent') {
            DeliveryAgent::create([
                'delivery_agent_id' => Str::uuid()->toString(),
                'user_id'           => $userId,
            ]);
        }

        if ($dto->role === 'shipping_company') {
            ShippingCompany::create([
                'shipping_company_id' => Str::uuid()->toString(),
                'user_id'             => $userId,
                'company_name'        => $dto->companyName,
            ]);
        }

        return ['user_id' => $userId];
    });
}
```

---

## 9. Use Case

**File:** `app/Modules/Users/Application/UseCases/ImportUsersUseCase.php`

```php
<?php

namespace App\Modules\Users\Application\UseCases;

use App\Modules\Users\Application\DTOs\ImportUserRowDTO;
use App\Modules\Users\Application\DTOs\ImportUsersResultDTO;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Excel\UsersImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportUsersUseCase
{
    private const ALLOWED_ROLES = ['delivery_agent', 'shipping_company'];

    public function __construct(
        private UserRepositoryInterface $repository,
    ) {}

    public function execute(UploadedFile $file): ImportUsersResultDTO
    {
        $import = new UsersImport();
        Excel::import($import, $file);

        $rows          = $import->getRows();
        $totalRows     = $rows->count();
        $importedItems = [];
        $errorItems    = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2: row 1 is header
            $rowArray  = $row->toArray();

            // ── Validate row ───────────────────────────────────────────
            $validation = $this->validateRow($rowArray, $rowNumber);

            if ($validation['has_errors']) {
                $errorItems[] = [
                    'row'    => $rowNumber,
                    'email'  => $rowArray['email'] ?? null,
                    'errors' => $validation['errors'],
                ];
                continue;
            }

            // ── Build DTO ──────────────────────────────────────────────
            $dto           = ImportUserRowDTO::fromRow($rowArray);
            $plainPassword = Str::password(12); // random 12-char password

            // ── Persist ────────────────────────────────────────────────
            try {
                $this->repository->createUserWithRole($dto, $plainPassword);

                $importedItems[] = [
                    'row'                => $rowNumber,
                    'name'               => $dto->name,
                    'email'              => $dto->email,
                    'generated_password' => $plainPassword,
                ];
            } catch (Throwable $e) {
                // DB-level failure (duplicate email/phone race condition, constraint violation)
                $errorItems[] = [
                    'row'    => $rowNumber,
                    'email'  => $dto->email,
                    'errors' => [__('users.import_row_db_error', ['message' => $e->getMessage()])],
                ];
            }
        }

        return new ImportUsersResultDTO(
            totalRows:     $totalRows,
            importedCount: count($importedItems),
            failedCount:   count($errorItems),
            imported:      $importedItems,
            errors:        $errorItems,
        );
    }

    // ────────────────────────────────────────────────────────────────────
    // Private helpers
    // ────────────────────────────────────────────────────────────────────

    private function validateRow(array $row, int $rowNumber): array
    {
        $rules = [
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'        => ['required', 'string', 'max:20', 'unique:users,phone'],
            'role'         => ['required', 'string', 'in:' . implode(',', self::ALLOWED_ROLES)],
            'company_name' => ['required_if:role,shipping_company', 'nullable', 'string', 'max:200'],
        ];

        $validator = Validator::make($row, $rules);

        if ($validator->fails()) {
            return [
                'has_errors' => true,
                'errors'     => $validator->errors()->all(),
            ];
        }

        return ['has_errors' => false, 'errors' => []];
    }
}
```

> **Why validate inside Use Case and not FormRequest?**
> Each *row* has its own validation context (`unique` is checked per row against the live DB, `required_if` depends on sibling columns). A FormRequest only validates the uploaded file itself (mime, size) — per-row logic belongs in the Use Case.

---

## 10. API Resource

**File:** `app/Modules/Users/Presentation/Http/Resources/ImportUsersResultResource.php`

```php
<?php

namespace App\Modules\Users\Presentation\Http\Resources;

use App\Modules\Users\Application\DTOs\ImportUsersResultDTO;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportUsersResultResource extends JsonResource
{
    // $this->resource is an ImportUsersResultDTO
    public function toArray($request): array
    {
        /** @var ImportUsersResultDTO $result */
        $result = $this->resource;

        return [
            'summary' => [
                'total_rows'     => $result->totalRows,
                'imported_count' => $result->importedCount,
                'failed_count'   => $result->failedCount,
            ],
            'imported' => $result->imported, // includes generated_password per row
            'errors'   => $result->errors,
        ];
    }
}
```

---

## 11. Controller Method

Add to the existing `AdminUserController`:

```php
use App\Modules\Users\Application\UseCases\ImportUsersUseCase;
use App\Modules\Users\Presentation\Http\Requests\ImportUsersRequest;
use App\Modules\Users\Presentation\Http\Resources\ImportUsersResultResource;

// — inject in constructor ——————————————————————————————————————
public function __construct(
    // ... existing use cases ...
    private ImportUsersUseCase $importUsers,
) {}

// — new method ——————————————————————————————————————————————
public function import(ImportUsersRequest $request): \Illuminate\Http\JsonResponse
{
    if (! auth()->user()->can('users.import')) {
        return ApiResponse::error(__('messages.forbidden'), 403);
    }

    $result   = $this->importUsers->execute($request->file('file'));
    $resource = new ImportUsersResultResource($result);

    $message = $result->failedCount === 0
        ? __('users.import_all_success', ['count' => $result->importedCount])
        : __('users.import_partial_success', [
            'imported' => $result->importedCount,
            'failed'   => $result->failedCount,
        ]);

    return ApiResponse::success($resource->toArray($request), $message);
}
```

---

## 12. Route

**File:** `app/Modules/Users/Presentation/Routes/api.php`

```php
// Inside the admin auth middleware group:
Route::post('users/import', [AdminUserController::class, 'import'])
     ->name('admin.users.import');
```

> Route is `POST` not `PUT` — we are uploading a file, not updating a resource.
> Must be declared **before** any `users/{userId}` wildcard routes to avoid the word "import" being captured as a UUID.

Full path: `POST /api/v1/admin/users/import`

---

## 13. New Permission

Add `users.import` to the PermissionsSeeder and assign to `super_admin`:

```php
$permission = Permission::firstOrCreate(['name' => 'users.import', 'guard_name' => 'api']);
Role::findByName('super_admin', 'api')->givePermissionTo($permission);
```

---

## 14. Language Strings

**`lang/ar/users.php`**
```php
'import_file_required'       => 'ملف الاستيراد مطلوب.',
'import_file_invalid_type'   => 'يجب أن يكون الملف بصيغة xlsx أو xls أو csv.',
'import_file_too_large'      => 'حجم الملف يتجاوز الحد المسموح (5 ميجابايت).',
'import_all_success'         => 'تم استيراد :count مستخدم بنجاح.',
'import_partial_success'     => 'تم استيراد :imported مستخدم. فشل استيراد :failed صف.',
'import_row_db_error'        => 'خطأ في قاعدة البيانات: :message',
```

**`lang/en/users.php`**
```php
'import_file_required'       => 'The import file is required.',
'import_file_invalid_type'   => 'The file must be an xlsx, xls, or csv.',
'import_file_too_large'      => 'The file exceeds the 5 MB limit.',
'import_all_success'         => 'Successfully imported :count users.',
'import_partial_success'     => 'Imported :imported users. :failed rows failed.',
'import_row_db_error'        => 'Database error: :message',
```

---

## 15. API Contract

### Request

```
POST /api/v1/admin/users/import
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: users.xlsx
```

### Success — all rows imported (200)

```json
{
  "status": true,
  "message": "تم استيراد 3 مستخدم بنجاح.",
  "data": {
    "summary": {
      "total_rows": 3,
      "imported_count": 3,
      "failed_count": 0
    },
    "imported": [
      { "row": 2, "name": "خالد العتيبي", "email": "khaled@example.com", "generated_password": "aB3$kLm9Xp2!" },
      { "row": 3, "name": "سارة محمود",   "email": "sara@example.com",   "generated_password": "Zp7#nRq4Wy1@" },
      { "row": 4, "name": "أحمد شريف",    "email": "ahmed@example.com",  "generated_password": "Lx9&mKj2Tn5%" }
    ],
    "errors": []
  }
}
```

### Partial success — some rows failed (200)

> HTTP 200 even on partial — the request itself succeeded; row failures are business-level, not HTTP-level.

```json
{
  "status": true,
  "message": "تم استيراد 2 مستخدم. فشل استيراد 1 صف.",
  "data": {
    "summary": {
      "total_rows": 3,
      "imported_count": 2,
      "failed_count": 1
    },
    "imported": [
      { "row": 2, "name": "خالد العتيبي", "email": "khaled@example.com", "generated_password": "aB3$kLm9Xp2!" },
      { "row": 3, "name": "سارة محمود",   "email": "sara@example.com",   "generated_password": "Zp7#nRq4Wy1@" }
    ],
    "errors": [
      {
        "row": 4,
        "email": "existing@example.com",
        "errors": ["The email has already been taken."]
      }
    ]
  }
}
```

### Error — invalid file (422)

```json
{
  "status": false,
  "message": "The given data was invalid.",
  "errors": {
    "file": ["يجب أن يكون الملف بصيغة xlsx أو xls أو csv."]
  }
}
```

### Error — no permission (403)

```json
{
  "status": false,
  "message": "غير مصرح بذلك.",
  "errors": null
}
```

---

## 16. Excel Template

Provide admins a downloadable template. Add a separate **no-queue** endpoint:

```
GET /api/v1/admin/users/import/template
```

```php
Route::get('users/import/template', [AdminUserController::class, 'importTemplate'])
     ->name('admin.users.importTemplate');

// Controller method:
public function importTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
{
    $path = storage_path('app/templates/users_import_template.xlsx');
    return response()->download($path, 'users_import_template.xlsx');
}
```

Store a hand-crafted `users_import_template.xlsx` (with Arabic headers in row 1 and one example row) under `storage/app/templates/`. This is a static file — no generation needed.

---

## 17. Security & Edge Cases

| Scenario | How it is handled |
|---|---|
| `super_admin` row in file | Validator rejects: role not in `['delivery_agent', 'shipping_company']` |
| Duplicate email within the same file (two rows same email) | First row saves, second row hits `unique:users,email` and goes to errors |
| File with 501+ rows | `WithLimit(500)` silently truncates; the response `total_rows` will show 500, not 501 — add a note in API docs |
| Malformed file / wrong format | Maatwebsite throws `\Maatwebsite\Excel\Exceptions\UnreadableFileException`; wrap `Excel::import()` in try/catch in the Use Case and return `ApiResponse::error()` |
| `company_name` missing for `shipping_company` row | `required_if` validator catches it — row goes to errors |
| Empty file (header only, 0 data rows) | `$rows->count() === 0` → returns summary with `total_rows: 0`, `imported_count: 0` and empty arrays |
| Passwords in response | `generated_password` is only in the import response body — **never** stored in plain text, never logged |
