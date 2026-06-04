<?php

namespace App\Modules\Users\Application\UseCases;

use App\Modules\Users\Application\DTOs\ImportUserRowDTO;
use App\Modules\Users\Application\DTOs\ImportUsersResultDTO;
use App\Modules\Users\Application\Exceptions\ImportUsersUnreadableFileException;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Excel\UsersImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Exceptions\NoTypeDetectedException;
use Maatwebsite\Excel\Exceptions\UnreadableFileException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportUsersUseCase
{
    private const ALLOWED_ROLES = ['delivery_agent', 'shipping_company'];

    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    public function execute(UploadedFile $file): ImportUsersResultDTO
    {
        $import = new UsersImport;

        try {
            Excel::import($import, $file);
        } catch (UnreadableFileException|NoTypeDetectedException $e) {
            throw new ImportUsersUnreadableFileException($e->getMessage(), 0, $e);
        }

        $rows = $import->getRows();
        $totalRows = $rows->count();
        $importedItems = [];
        $errorItems = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $rowArray = $this->normalizeRowArray($row->toArray());

            $validation = $this->validateRow($rowArray);

            if ($validation['has_errors']) {
                $errorItems[] = [
                    'row' => $rowNumber,
                    'email' => $rowArray['email'] ?? null,
                    'errors' => $validation['errors'],
                ];

                continue;
            }

            $dto = ImportUserRowDTO::fromRow($rowArray);
            $plainPassword = Str::password(12);

            try {
                $this->repository->createUserWithRole($dto, $plainPassword);

                $importedItems[] = [
                    'row' => $rowNumber,
                    'name' => $dto->name,
                    'email' => $dto->email,
                    'generated_password' => $plainPassword,
                ];
            } catch (Throwable $e) {
                $errorItems[] = [
                    'row' => $rowNumber,
                    'email' => $dto->email,
                    'errors' => [__('users::messages.import_row_db_error', ['message' => $e->getMessage()])],
                ];
            }
        }

        return new ImportUsersResultDTO(
            totalRows: $totalRows,
            importedCount: count($importedItems),
            failedCount: count($errorItems),
            imported: $importedItems,
            errors: $errorItems,
        );
    }

    /**
     * @return array{has_errors: bool, errors: array<int, string>}
     */
    private function validateRow(array $row): array
    {
        $role = strtolower(trim((string) ($row['role'] ?? '')));

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'role' => ['required', 'string', Rule::in(self::ALLOWED_ROLES)],
            'company_name' => [
                Rule::requiredIf($role === 'shipping_company'),
                'nullable',
                'string',
                'max:200',
            ],
        ];

        $validator = Validator::make($row, $rules);

        if ($validator->fails()) {
            return [
                'has_errors' => true,
                'errors' => $validator->errors()->all(),
            ];
        }

        return ['has_errors' => false, 'errors' => []];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRowArray(array $row): array
    {
        $aliases = [
            'name' => ['name', 'الاسم'],
            'email' => ['email', 'البريد_الالكتروني', 'البريد الإلكتروني'],
            'phone' => ['phone', 'الهاتف'],
            'gender' => ['gender', 'الجنس'],
            'role' => ['role', 'الدور'],
            'company_name' => ['company_name', 'اسم_الشركة', 'اسم الشركة'],
        ];

        $normalized = [];

        foreach ($aliases as $canonical => $keys) {
            foreach ($keys as $key) {
                $slug = Str::slug($key, '_');
                if (array_key_exists($key, $row)) {
                    $normalized[$canonical] = $row[$key];
                    break;
                }
                if (array_key_exists($slug, $row)) {
                    $normalized[$canonical] = $row[$slug];
                    break;
                }
            }
        }

        foreach ($row as $key => $value) {
            $slug = is_string($key) ? Str::slug($key, '_') : $key;
            if (! isset($normalized[$slug]) && in_array($slug, ['name', 'email', 'phone', 'gender', 'role', 'company_name'], true)) {
                $normalized[$slug] = $value;
            }
        }

        return $normalized;
    }
}
