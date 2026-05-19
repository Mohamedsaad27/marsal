<?php

namespace App\Modules\Auth\Application\UseCases;

use Tymon\JWTAuth\Facades\JWTAuth;

class RefreshTokenUseCase
{
    public function execute(): array
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }
}
