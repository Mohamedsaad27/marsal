<?php

namespace App\Modules\Auth\Domain\Interfaces;

use App\Modules\Auth\Infrastructure\Database\Models\PasswordResetOtp;
use App\Modules\Users\Infrastructure\Database\Models\User;

interface PasswordResetOtpRepositoryInterface
{
    public function createForUser(User $user, string $plainOtp): PasswordResetOtp;

    public function findActiveByEmail(string $email): ?PasswordResetOtp;

    public function incrementAttempts(PasswordResetOtp $otp): void;

    public function markAsUsed(PasswordResetOtp $otp): void;

    public function invalidateAllForUser(string $userId): void;

    public function countRequestsInLastHour(string $email): int;

    public function latestCreatedAtForEmail(string $email): ?\Illuminate\Support\Carbon;
}
