<?php

namespace App\Modules\Auth\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class LogoutUseCase
{
    public function __construct(
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(): void
    {
        $user = Auth::user();
        $userId = $user?->getAuthIdentifier();

        JWTAuth::invalidate(JWTAuth::getToken());

        if ($userId !== null) {
            $this->recordAudit->execute(
                userId:        $userId,
                event:         AuditEventEnum::Logout,
                auditableType: 'users',
                auditableId:   $userId,
                metadata:      ['name' => $user->name],
            );
        }
    }
}
