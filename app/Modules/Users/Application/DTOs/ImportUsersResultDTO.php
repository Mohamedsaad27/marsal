<?php

namespace App\Modules\Users\Application\DTOs;

readonly class ImportUsersResultDTO
{
    /**
     * @param  array<int, array{row: int, name: string, email: string, generated_password: string}>  $imported
     * @param  array<int, array{row: int, email: string|null, errors: array<int, string>}>  $errors
     */
    public function __construct(
        public int $totalRows,
        public int $importedCount,
        public int $failedCount,
        public array $imported,
        public array $errors,
    ) {}
}
