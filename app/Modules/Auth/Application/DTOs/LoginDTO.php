<?php

namespace App\Modules\Auth\Application\DTOs;

readonly class LoginDTO
{
    public function __construct(
        public string $identifier,
        public string $password,
    ) {}
}
