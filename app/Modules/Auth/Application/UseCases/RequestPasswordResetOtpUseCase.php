<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Application\Exceptions\OtpRateLimitException;
use App\Modules\Auth\Application\Exceptions\OtpResendCooldownException;
use App\Modules\Auth\Domain\Interfaces\PasswordResetOtpRepositoryInterface;
use App\Modules\Auth\Domain\Services\OtpGenerator;
use App\Modules\Auth\Infrastructure\Jobs\SendPasswordResetOtpEmailJob;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;

class RequestPasswordResetOtpUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordResetOtpRepositoryInterface $otpRepository,
        private readonly OtpGenerator $otpGenerator,
    ) {}

    public function execute(string $email): void
    {
        $email = strtolower(trim($email));

        $user = $this->userRepository->findByEmail($email);

        if ($user === null || ! $user->is_active) {
            return;
        }

        $maxPerHour = (int) config('auth_module.otp_max_requests_per_hour', 5);
        if ($this->otpRepository->countRequestsInLastHour($email) >= $maxPerHour) {
            throw new OtpRateLimitException();
        }

        $latest = $this->otpRepository->latestCreatedAtForEmail($email);
        $cooldown = (int) config('auth_module.otp_resend_cooldown_seconds', 60);

        if ($latest !== null && $latest->diffInSeconds(now()) < $cooldown) {
            throw new OtpResendCooldownException($cooldown - $latest->diffInSeconds(now()));
        }

        $plainOtp = $this->otpGenerator->generate();
        $this->otpRepository->createForUser($user, $plainOtp);

        SendPasswordResetOtpEmailJob::dispatch($user->email, $user->name, $plainOtp);
    }
}
