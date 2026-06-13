<?php

namespace App\Modules\Users\Application\DTOs;

use App\Modules\Users\Domain\Enums\AccountTypeEnum;

readonly class CreateUserDTO
{
    /**
     * @param  array<int, string>  $roles
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>  $address
     */
    public function __construct(
        public string $name,
        public ?string $email,
        public string $phone,
        public string $password,
        public AccountTypeEnum $accountType,
        public array $roles,
        public array $profile = [],
        public array $address = [],
    ) {}
}
