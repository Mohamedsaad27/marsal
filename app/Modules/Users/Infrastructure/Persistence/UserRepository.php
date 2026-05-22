<?php

namespace App\Modules\Users\Infrastructure\Persistence;

use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use App\Modules\Users\Infrastructure\Database\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function findById(string $userId): ?User
    {
        return User::query()->find($userId);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function findByPhone(string $phone): ?User
    {
        return User::query()->where('phone', $phone)->first();
    }

    public function findByLogin(string $login): ?User
    {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return $this->findByEmail($login);
        }

        return $this->findByPhone($login);
    }

    public function updateLastLogin(User $user): void
    {
        $user->update(['last_login_at' => now()]);
    }

    public function updatePassword(User $user, string $plainPassword): void
    {
        $user->update(['password' => $plainPassword]);
    }

    public function updateWelcomeWhatsAppUrl(User $user, string $url): void
    {
        $user->update(['welcome_whatsapp_url' => $url]);
    }
}
