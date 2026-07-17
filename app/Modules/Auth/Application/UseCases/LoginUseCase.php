<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Auth\Application\DTOs\LoginDTO;
use App\Modules\Auth\Application\Exceptions\InvalidCredentialsException;
use App\Modules\Users\Domain\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(LoginDTO $dto): array
    {
        $user = $this->userRepository->findByLogin($dto->identifier);

        if ($user === null || ! $user->is_active || ! Hash::check($dto->password, $user->getAuthPassword())) {
            throw new InvalidCredentialsException();
        }

        $token = JWTAuth::fromUser($user);

        $this->userRepository->updateLastLogin($user);

        if ($dto->fcmToken !== null) {
            $this->userRepository->update($user, ['fcm_token' => $dto->fcmToken]);
        }

        $this->recordAudit->execute(
            userId:        $user->user_id,
            event:         AuditEventEnum::Login,
            auditableType: 'users',
            auditableId:   $user->user_id,
            metadata:      [
                'ip'   => request()?->ip(),
                'name' => $user->name,
            ],
        );

        $user->load(['shippingCompany', 'deliveryAgent', 'staffMember.department']);

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => $user,
        ];
    }
}
