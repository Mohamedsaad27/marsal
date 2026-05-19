<?php

namespace App\Modules\Auth\Presentation\Http\Controllers;

use App\Modules\Auth\Application\UseCases\GetAuthenticatedUserUseCase;
use App\Modules\Auth\Application\UseCases\LoginUseCase;
use App\Modules\Auth\Application\UseCases\LogoutUseCase;
use App\Modules\Auth\Application\UseCases\RefreshTokenUseCase;
use App\Modules\Auth\Presentation\Http\Requests\LoginRequest;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Users\Presentation\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly LoginUseCase $loginUseCase,
        private readonly LogoutUseCase $logoutUseCase,
        private readonly RefreshTokenUseCase $refreshTokenUseCase,
        private readonly GetAuthenticatedUserUseCase $getAuthenticatedUserUseCase,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->loginUseCase->execute($request->toDTO());

        return $this->success([
            'user' => new UserResource($result['user']),
            'access_token' => $result['access_token'],
            'token_type' => $result['token_type'],
            'expires_in' => $result['expires_in'],
        ], __('auth::messages.login_success'));
    }

    public function logout(): JsonResponse
    {
        $this->logoutUseCase->execute();

        return $this->success(null, __('auth::messages.logout_success'));
    }

    public function refresh(): JsonResponse
    {
        $result = $this->refreshTokenUseCase->execute();

        return $this->success($result, __('auth::messages.token_refreshed'));
    }

    public function me(): JsonResponse
    {
        $user = $this->getAuthenticatedUserUseCase->execute();

        return $this->success(new UserResource($user), __('auth::messages.profile_loaded'));
    }
}
