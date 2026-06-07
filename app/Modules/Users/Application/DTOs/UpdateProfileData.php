<?php

namespace App\Modules\Users\Application\DTOs;

use App\Modules\Users\Presentation\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\UploadedFile;

readonly class UpdateProfileData
{
    /** @param  list<string>  $presentKeys */
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $gender = null,
        public ?UploadedFile $avatar = null,
        public array $presentKeys = [],
    ) {}

    public static function fromRequest(UpdateProfileRequest $request): self
    {
        $presentKeys = [];

        foreach (['name', 'email', 'phone', 'gender'] as $field) {
            if ($request->has($field)) {
                $presentKeys[] = $field;
            }
        }

        if ($request->hasFile('avatar')) {
            $presentKeys[] = 'avatar';
        }

        return new self(
            name: $request->has('name') ? $request->string('name')->toString() : null,
            email: $request->has('email') ? $request->string('email')->toString() : null,
            phone: $request->has('phone') ? $request->string('phone')->toString() : null,
            gender: $request->has('gender') ? $request->string('gender')->toString() : null,
            avatar: $request->hasFile('avatar') ? $request->file('avatar') : null,
            presentKeys: $presentKeys,
        );
    }

    /** @return array<string, mixed> */
    public function userAttributes(): array
    {
        $attributes = [];

        foreach (['name', 'email', 'phone', 'gender'] as $field) {
            if (in_array($field, $this->presentKeys, true)) {
                $attributes[$field] = $this->{$field};
            }
        }

        return $attributes;
    }

    public function hasAvatar(): bool
    {
        return in_array('avatar', $this->presentKeys, true) && $this->avatar !== null;
    }
}
