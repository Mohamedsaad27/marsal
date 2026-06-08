<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Locations\Application\DTOs\CreateGovernorateDTO;
use App\Modules\Locations\Application\Services\LocationCodeGenerator;
use App\Modules\Locations\Domain\Interfaces\GovernorateRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\Governorate;
use Illuminate\Support\Facades\Auth;

class CreateGovernorateUseCase
{
    public function __construct(
        private readonly GovernorateRepositoryInterface $governorateRepository,
        private readonly LocationCodeGenerator $codeGenerator,
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(CreateGovernorateDTO $dto): Governorate
    {
        $code = $dto->code
            ? $this->codeGenerator->forGovernorate($dto->name_en, $dto->code)
            : $this->codeGenerator->forGovernorate($dto->name_en);

        $governorate = $this->governorateRepository->create([
            'name_ar' => $dto->name_ar,
            'name_en' => $dto->name_en,
            'code' => $code,
            'is_active' => $dto->is_active,
        ])->loadCount('cities');

        $this->recordAudit->execute(
            userId:        Auth::id(),
            event:         AuditEventEnum::Created,
            auditableType: 'governorates',
            auditableId:   $governorate->governorate_id,
            newValues:     $this->governorateAuditSnapshot($governorate),
        );

        return $governorate;
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
