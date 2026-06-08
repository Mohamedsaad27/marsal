<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Locations\Domain\Interfaces\GovernorateRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\Governorate;
use Illuminate\Support\Facades\Auth;

class ToggleGovernorateStatusUseCase
{
    public function __construct(
        private readonly GovernorateRepositoryInterface $governorateRepository,
        private readonly GetGovernorateUseCase $getGovernorateUseCase,
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(string $governorateId): Governorate
    {
        $governorate = $this->getGovernorateUseCase->execute($governorateId);
        $wasActive = $governorate->is_active;

        $governorate = $this->governorateRepository->toggleStatus($governorate);

        $this->recordAudit->execute(
            userId:        Auth::id(),
            event:         $wasActive ? AuditEventEnum::Deactivated : AuditEventEnum::Activated,
            auditableType: 'governorates',
            auditableId:   $governorate->governorate_id,
            oldValues:     ['is_active' => $wasActive],
            newValues:     ['is_active' => $governorate->is_active],
            metadata:      ['name_en' => $governorate->name_en],
        );

        return $governorate;
    }
}
