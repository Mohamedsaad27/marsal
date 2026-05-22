<?php

namespace App\Modules\Auth\Infrastructure\Persistence;

use App\Modules\Auth\Domain\Interfaces\PasswordResetOtpRepositoryInterface;
use App\Modules\Auth\Infrastructure\Database\Models\PasswordResetOtp;
use App\Modules\Users\Infrastructure\Database\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class PasswordResetOtpRepository implements PasswordResetOtpRepositoryInterface
{
    public function createForUser(User $user, string $plainOtp): PasswordResetOtp
    {
        $this->invalidateAllForUser($user->user_id);

        $expiryMinutes = (int) config('auth_module.otp_expiry_minutes', 15);

        return PasswordResetOtp::query()->create([
            'user_id' => $user->user_id,
            'email' => $user->email,
            'otp_hash' => Hash::make($plainOtp),
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);
    }

    public function findActiveByEmail(string $email): ?PasswordResetOtp
    {
        return PasswordResetOtp::query()
            ->where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();
    }

    public function incrementAttempts(PasswordResetOtp $otp): void
    {
        $otp->increment('attempts');
    }

    public function markAsUsed(PasswordResetOtp $otp): void
    {
        $otp->update(['used_at' => now()]);
    }

    public function invalidateAllForUser(string $userId): void
    {
        PasswordResetOtp::query()
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);
    }

    public function countRequestsInLastHour(string $email): int
    {
        return PasswordResetOtp::query()
            ->where('email', $email)
            ->where('created_at', '>=', now()->subHour())
            ->count();
    }

    public function latestCreatedAtForEmail(string $email): ?Carbon
    {
        $latest = PasswordResetOtp::query()
            ->where('email', $email)
            ->latest('created_at')
            ->value('created_at');

        return $latest ? Carbon::parse($latest) : null;
    }
}
