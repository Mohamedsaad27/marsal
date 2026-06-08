<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Locations\Application\Exceptions\LocationInUseException;
use App\Modules\Locations\Domain\Interfaces\GovernorateRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\City;
use App\Modules\Locations\Infrastructure\Database\Models\Governorate;
use Illuminate\Support\Facades\Auth;

class DeleteGovernorateUseCase
{
    public function __construct(
        private readonly GovernorateRepositoryInterface $governorateRepository,
        private readonly GetGovernorateUseCase $getGovernorateUseCase,
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(string $governorateId): void
    {
        $governorate = $this->getGovernorateUseCase->execute($governorateId);

        if (City::query()->where('governorate_id', $governorateId)->exists()) {
            throw new LocationInUseException(__('locations::messages.governorate_has_cities'));
        }

        if ($this->governorateRepository->isGovernorateReferenced($governorateId)) {
            throw new LocationInUseException;
        }

        $this->recordAudit->execute(
            userId:        Auth::id(),
            event:         AuditEventEnum::Deleted,
            auditableType: 'governorates',
            auditableId:   $governorate->governorate_id,
            oldValues:     $this->governorateAuditSnapshot($governorate),
        );

        $this->governorateRepository->delete($governorate);
    }

    private function governorateAuditSnapshot(Governorate $governorate): array
    {
        return [
            'name_ar'   => $governorate->name_ar,
            'name_en'   => $governorate->name_en,
            'code'      => $governorate->code,
            'is_active' => $governorate->is_active,
        ];
    }
}
