<?php

namespace App\Modules\Auth\Application\UseCases;

use Tymon\JWTAuth\Facades\JWTAuth;

class LogoutUseCase
{
    public function execute(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }
}
