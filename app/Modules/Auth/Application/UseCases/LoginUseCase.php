<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\Auth\Application\DTOs\LoginDTO;
use App\Modules\Auth\Application\Exceptions\InvalidCredentialsException;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function execute(LoginDTO $dto): array
    {
        $user = $this->userRepository->findByLogin($dto->identifier);

        if ($user === null || ! $user->is_active || ! Hash::check($dto->password, $user->getAuthPassword())) {
            throw new InvalidCredentialsException();
        }

        $token = JWTAuth::fromUser($user);

        $this->userRepository->updateLastLogin($user);

        $user->load(['shippingCompany', 'deliveryAgent', 'staffMember']);

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => $user,
        ];
    }
}
