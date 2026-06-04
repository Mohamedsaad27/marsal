<?php

namespace App\Modules\Users\Application\DTOs;

readonly class ImportUserRowDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public ?string $gender,
        public string $role,
        public ?string $companyName,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            name: trim((string) ($row['name'] ?? '')),
            email: strtolower(trim((string) ($row['email'] ?? ''))),
            phone: trim((string) ($row['phone'] ?? '')),
            gender: filled($row['gender'] ?? null) ? trim((string) $row['gender']) : null,
            role: strtolower(trim((string) ($row['role'] ?? ''))),
            companyName: filled($row['company_name'] ?? null) ? trim((string) $row['company_name']) : null,
        );
    }
}
