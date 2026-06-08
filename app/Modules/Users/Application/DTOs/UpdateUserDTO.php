<?php

namespace App\Modules\Users\Application\DTOs;

readonly class UpdateUserDTO
{
    /** @param  list<string>  $presentKeys */
    public function __construct(
        public string $userId,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $gender = null,
        public ?string $avatar = null,
        /** @var list<string>|null */
        public ?array $roles = null,
        /** @var array<string, mixed>|null */
        public ?array $profile = null,
        /** @var array<string, mixed>|null */
        public ?array $address = null,
        public array $presentKeys = [],
    ) {}

    public static function fromArray(string $userId, array $data): self
    {
        return new self(
            userId: $userId,
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            gender: $data['gender'] ?? null,
            avatar: $data['avatar'] ?? null,
            roles: $data['roles'] ?? null,
            profile: $data['profile'] ?? null,
            address: $data['address'] ?? null,
            presentKeys: array_keys($data),
        );
    }

    public function userAttributes(): array
    {
        $attributes = [];

        foreach (['name', 'email', 'phone', 'gender', 'avatar'] as $field) {
            if (in_array($field, $this->presentKeys, true)) {
                $attributes[$field] = $this->{$field};
            }
        }

        return $attributes;
    }
}
