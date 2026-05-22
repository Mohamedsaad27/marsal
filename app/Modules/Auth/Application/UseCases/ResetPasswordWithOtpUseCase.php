<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Application\Exceptions\InvalidOtpException;
use App\Modules\Auth\Application\Exceptions\OtpExpiredException;
use App\Modules\Auth\Application\Exceptions\PasswordReuseException;
use App\Modules\Auth\Domain\Interfaces\PasswordResetOtpRepositoryInterface;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class ResetPasswordWithOtpUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordResetOtpRepositoryInterface $otpRepository,
    ) {}

    public function execute(string $email, string $otp, string $newPassword): void
    {
        $email = strtolower(trim($email));
        $otp = trim($otp);

        $record = $this->otpRepository->findActiveByEmail($email);

        if ($record === null) {
            throw new OtpExpiredException();
        }

        if (! $record->isUsable()) {
            throw new OtpExpiredException();
        }

        if (! Hash::check($otp, $record->otp_hash)) {
            $this->otpRepository->incrementAttempts($record);

            throw new InvalidOtpException();
        }

        $user = $this->userRepository->findByEmail($email);

        if ($user === null || ! $user->is_active) {
            throw new OtpExpiredException();
        }

        if (Hash::check($newPassword, $user->password)) {
            throw new PasswordReuseException();
        }

        if (strcasecmp($newPassword, $user->email) === 0) {
            throw new PasswordReuseException(__('auth::messages.password_same_as_email'));
        }

        $this->userRepository->updatePassword($user, $newPassword);
        $this->otpRepository->markAsUsed($record);
        $this->otpRepository->invalidateAllForUser($user->user_id);
    }
}
