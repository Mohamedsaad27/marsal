# User Management Refactor Plan — Add `account_type` Column to `users` Table

## Current State

- The `users` table has no `account_type` column. The user type is currently resolved at runtime via `resolveAccountType()`, which checks the user's roles or related models (`staffMember` / `shippingCompany` / `deliveryAgent`).
- `AccountTypeEnum` already exists with values: `SuperAdmin=1`, `ShippingCompany=2`, `DeliveryAgent=3`, `StaffMember=4`.
- Routes are split: the generic route (`POST /users`) accepts `account_type` + `roles[]`, while specific routes (`POST /staff-members`, `/shipping-companies`, `/delivery-agents`) infer the type from the route itself.

---

## Goals

1. Add an `account_type` (tinyint) column to `users` as the single source of truth.
2. **Generic route:** accepts `type` + `role` from the request body.
3. **Specific routes:** accept `role` only — `type` is determined automatically by the route.

---

## Implementation Steps

### Step 1 — Migration: Add `account_type` Column

**File:** `Users/Infrastructure/Database/Migrations/2026_XX_XX_add_account_type_to_users_table.php`

```php
Schema::table('users', function (Blueprint $table) {
    $table->tinyInteger('account_type')->unsigned()->nullable()->after('is_active')
          ->comment('AccountTypeEnum: 1=super_admin|2=shipping_company|3=delivery_agent|4=staff_member');
});
```

> **Note:** Start as `nullable` to allow backfilling existing records, then make it `NOT NULL` once the backfill is complete.

---

### Step 2 — Backfill Seeder: Populate Existing Records

**New file:** `Users/Infrastructure/Database/Seeders/BackfillAccountTypeSeeder.php`

```php
// For each existing user, determine account_type from their role
User::with('roles')->each(function (User $user) {
    $type = $user->resolveAccountType();
    if ($type) {
        $user->updateQuietly(['account_type' => $type->value]);
    }
});
```

---

### Step 3 — User Model: Add `account_type` to `$fillable` and Casts

**File:** `Users/Infrastructure/Database/Models/User.php`

```php
// Add to $fillable:
'account_type',

// Add to casts():
'account_type' => AccountTypeEnum::class,
```

Update `resolveAccountType()` to read from the column first:

```php
public function resolveAccountType(): ?AccountTypeEnum
{
    if ($this->account_type !== null) {
        return $this->account_type; // auto-cast from Enum
    }

    // Fallback for old records (remove after backfill is confirmed complete)
    // ... existing logic
}
```

---

### Step 4 — CreateUserUseCase: Persist `account_type` on Creation

**File:** `Users/Application/UseCases/CreateUserUseCase.php`

```php
$user = $this->userRepository->create([
    'name'         => $dto->name,
    'email'        => $dto->email,
    'phone'        => $dto->phone,
    'password'     => $dto->password,
    'is_active'    => true,
    'account_type' => $dto->accountType->value, // ← add this
]);
```

---

### Step 5 — GetUsersDTO: Add `accountType` Filter

**File:** `Users/Application/DTOs/GetUsersDTO.php`

```php
public function __construct(
    public ?string $search = null,
    public ?string $role = null,
    public ?AccountTypeEnum $accountType = null, // ← add
    // ... other properties
) {}

public static function fromArray(array $data): self
{
    return new self(
        // ...
        accountType: isset($data['type'])
            ? AccountTypeEnum::fromCode($data['type'])
            : null,
    );
}

public function withAccountType(AccountTypeEnum $type): self
{
    return new self(/* ... */ accountType: $type, /* ... */);
}
```

---

### Step 6 — UserRepository: Apply `account_type` Filter in Query

**File:** `Users/Infrastructure/Persistence/UserRepository.php`

Inside `getUsers()`, add:

```php
->when($dto->accountType, fn ($q) => $q->where('account_type', $dto->accountType->value))
```

---

### Step 7 — Routes: Restructure

**File:** `Users/Presentation/Routes/admin.php`

#### Generic Route (accepts `type` + `role`)
```php
// POST /api/v1/admin/users
// Body: { type, role, name, email, phone, password, profile?, address? }
Route::post('users', [AdminUserController::class, 'store'])
    ->middleware('permission:users.create');
```

#### Specific Routes (accept `role` only)
```php
// POST /api/v1/admin/staff-members
// Body: { role, name, email, phone, password, profile?, address? }
Route::post('staff-members', [AdminUserController::class, 'storeStaffMember'])
    ->middleware('permission:users.create');

// POST /api/v1/admin/shipping-companies
// Body: { role, name, email, phone, password, profile?, address? }
Route::post('shipping-companies', [AdminUserController::class, 'storeShippingCompany'])
    ->middleware('permission:shipping_companies.create');

// POST /api/v1/admin/delivery-agents
// Body: { role, name, email, phone, password, profile?, address? }
Route::post('delivery-agents', [AdminUserController::class, 'storeDeliveryAgent'])
    ->middleware('permission:delivery_agents.create');
```

---

### Step 8 — Requests: Update Validation Rules

#### `CreateUserRequest.php` (Generic Route)

```php
public function rules(): array
{
    return [
        'type'     => ['required', 'string', Rule::in(AccountTypeEnum::codes())], // ← was account_type
        'role'     => ['required', 'string', 'max:100'],                          // ← was roles[] array
        'name'     => ['required', 'string', 'max:255'],
        'email'    => ['required', 'email', 'unique:users,email'],
        'phone'    => ['required', 'string', 'max:20', 'unique:users,phone'],
        'password' => ['required', 'string', 'min:8'],
        'profile'  => ['nullable', 'array'],
        // ... remaining profile and address rules
    ];
}

public function toDTO(): CreateUserDTO
{
    return new CreateUserDTO(
        // ...
        accountType: AccountTypeEnum::fromCode($this->string('type')->toString()),
        roles: [$this->string('role')->toString()],
    );
}
```

#### `StoreStaffMemberRequest.php` (Specific Route)

```php
public function rules(): array
{
    return [
        'role'     => ['required', 'string', 'max:100'], // ← role only, no type
        'name'     => ['required', 'string', 'max:255'],
        'email'    => ['required', 'email', 'unique:users,email'],
        'phone'    => ['required', 'string', 'max:20', 'unique:users,phone'],
        'password' => ['required', 'string', 'min:8'],
        'profile'  => ['nullable', 'array'],
        // ...
    ];
}
```

Apply the same pattern to `StoreShippingCompanyRequest.php` and `StoreDeliveryAgentRequest.php`.

---

### Step 9 — Controller: Auto-resolve Type in Specific Store Methods

**File:** `Users/Presentation/Http/Controllers/AdminUserController.php`

```php
public function storeStaffMember(StoreStaffMemberRequest $request): JsonResponse
{
    $dto = new CreateUserDTO(
        name:        $request->string('name')->toString(),
        email:       $request->string('email')->toString(),
        phone:       $request->string('phone')->toString(),
        password:    $request->string('password')->toString(),
        accountType: AccountTypeEnum::StaffMember,           // ← hardcoded by route
        roles:       [$request->string('role')->toString()], // ← role from request
        profile:     $request->input('profile', []),
        address:     $request->input('address', []),
    );

    $user = $this->createUserUseCase->execute($dto);

    return $this->success(new UserResource($user), __('users::messages.staff_created'), 201);
}

// Apply the same pattern to storeShippingCompany() and storeDeliveryAgent()
```

---

### Step 10 — JWT Claims: Read Directly from Column

**File:** `Users/Infrastructure/Database/Models/User.php`

```php
public function getJWTCustomClaims(): array
{
    return [
        'account_type' => $this->account_type?->code(), // ← direct from column, no resolution needed
        'roles'        => $this->getRoleNames()->values()->all(),
        'permissions'  => $this->getAllPermissions()->pluck('name')->values()->all(),
    ];
}
```

---

### Step 11 — UserRepository: Update `createUserWithRole` (Excel Import Flow)

**File:** `Users/Infrastructure/Persistence/UserRepository.php`

Inside `createUserWithRole()`, resolve and persist `account_type`:

```php
$accountType = match($dto->role) {
    'delivery_agent'   => AccountTypeEnum::DeliveryAgent,
    'shipping_company' => AccountTypeEnum::ShippingCompany,
    'staff_member'     => AccountTypeEnum::StaffMember,
    default            => AccountTypeEnum::StaffMember,
};

$user = User::query()->create([
    'name'         => $dto->name,
    'email'        => $dto->email,
    'phone'        => $dto->phone,
    'gender'       => $dto->gender,
    'password'     => $plainPassword,
    'is_active'    => true,
    'account_type' => $accountType->value, // ← add
]);
```

---

## Execution Order in Cursor

```
1.  Migration           — add account_type column (nullable)
2.  User Model          — $fillable + cast + update resolveAccountType()
3.  BackfillSeeder      — populate account_type for existing records
4.  CreateUserUseCase   — persist account_type on user creation
5.  UserRepository      — update createUserWithRole() (import flow)
6.  GetUsersDTO         — add accountType property + fromArray() mapping
7.  UserRepository      — add account_type filter in getUsers()
8.  CreateUserRequest   — change account_type+roles[] → type+role
9.  Specific Requests   — change roles[] → role only
10. AdminUserController — update store() + specific store methods
11. Routes              — document new request body in comments
12. JWT Claims          — simplify getJWTCustomClaims()
13. Run migrations      — php artisan migrate
14. Run backfill        — php artisan db:seed --class=BackfillAccountTypeSeeder
15. Make NOT NULL       — add second migration to remove nullable
```

---

## API Diff Summary

| Route | Before | After |
|---|---|---|
| `POST /users` | `account_type` + `roles[]` | `type` + `role` |
| `POST /staff-members` | `roles[]` | `role` |
| `POST /shipping-companies` | `roles[]` | `role` |
| `POST /delivery-agents` | `roles[]` | `role` |
| `GET /users` | filter by `role` | filter by `type` or `role` |
