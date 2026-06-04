<?php

namespace App\Modules\Users\Application\DTOs;

readonly class AdminChangeUserPasswordDTO
{
    public function __construct(
        public string $userId,
        public string $password,
    ) {}

    public static function fromArray(string $userId, array $data): self
    {
        return new self(
            userId: $userId,
            password: $data['password'],
        );
    }
}
