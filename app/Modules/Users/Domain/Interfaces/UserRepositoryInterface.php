<?php

namespace App\Modules\Users\Domain\Interfaces;

use App\Modules\Users\Infrastructure\Database\Models\User;

interface UserRepositoryInterface
{
    public function create(array $attributes): User;

    public function findById(string $userId): ?User;

    public function findByEmail(string $email): ?User;

    public function findByPhone(string $phone): ?User;

    public function findByLogin(string $login): ?User;

    public function updateLastLogin(User $user): void;

    public function updatePassword(User $user, string $plainPassword): void;

    public function updateWelcomeWhatsAppUrl(User $user, string $url): void;
}
