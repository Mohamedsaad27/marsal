<?php

namespace App\Modules\Auth\Domain\Services;

class OtpGenerator
{
    public function generate(): string
    {
        $length = (int) config('auth_module.otp_length', 6);
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }
}
