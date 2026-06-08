<?php

namespace App\Modules\AuditLog\Application\UseCases;

use App\Modules\AuditLog\Application\Services\AuditDescriptionBuilder;
use App\Modules\AuditLog\Domain\Enums\AuditActorTypeEnum;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\AuditLog\Domain\Interfaces\AuditLogRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class RecordAuditUseCase
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
        private readonly AuditDescriptionBuilder $descriptionBuilder,
    ) {}

    public function execute(
        ?string        $userId,
        AuditEventEnum $event,
        string         $auditableType,
        string         $auditableId,
        array          $oldValues = [],
        array          $newValues = [],
        array          $metadata  = [],
        ?string        $description = null,
    ): void {
        $description ??= $this->descriptionBuilder->build(
            $event,
            $auditableType,
            $oldValues,
            $newValues,
            $metadata,
        );

        $this->repository->record(
            userId:        $userId,
            actorType:     $this->resolveActorType($userId),
            event:         $event,
            auditableType: $auditableType,
            auditableId:   $auditableId,
            oldValues:     $oldValues,
            newValues:     $newValues,
            metadata:      $metadata,
            description:   $description,
            ipAddress:     request()?->ip(),
            userAgent:     request()?->userAgent(),
        );
    }

    private function resolveActorType(?string $userId): int
    {
        if ($userId === null) {
            return AuditActorTypeEnum::System->value;
        }

        $user = Auth::user();

        if ($user === null || $user->getAuthIdentifier() !== $userId) {
            return AuditActorTypeEnum::System->value;
        }

        return match (true) {
            $user->hasRole('super_admin')      => AuditActorTypeEnum::SuperAdmin->value,
            $user->hasRole('shipping_company') => AuditActorTypeEnum::ShippingCompany->value,
            $user->hasRole('delivery_agent')   => AuditActorTypeEnum::DeliveryAgent->value,
            default                            => AuditActorTypeEnum::System->value,
        };
    }
}
