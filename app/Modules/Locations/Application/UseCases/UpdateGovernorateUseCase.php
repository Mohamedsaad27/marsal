<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Locations\Application\DTOs\UpdateGovernorateDTO;
use App\Modules\Locations\Application\Services\LocationCodeGenerator;
use App\Modules\Locations\Domain\Interfaces\GovernorateRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\Governorate;
use Illuminate\Support\Facades\Auth;

class UpdateGovernorateUseCase
{
    public function __construct(
        private readonly GovernorateRepositoryInterface $governorateRepository,
        private readonly GetGovernorateUseCase $getGovernorateUseCase,
        private readonly LocationCodeGenerator $codeGenerator,
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(string $governorateId, UpdateGovernorateDTO $dto): Governorate
    {
        $governorate = $this->getGovernorateUseCase->execute($governorateId);
        $oldValues = $this->governorateAuditSnapshot($governorate);

        $code = $dto->code ?? $governorate->code;

        if ($code === null || $code === '') {
            $code = $this->codeGenerator->forGovernorate($dto->name_en);
        } elseif ($code !== $governorate->code) {
            $code = $this->codeGenerator->forGovernorate($dto->name_en, $code);
        }

        $governorate = $this->governorateRepository->update($governorate, [
            'name_ar' => $dto->name_ar,
            'name_en' => $dto->name_en,
            'code' => $code,
            'is_active' => $dto->is_active,
        ]);

        $this->recordAudit->execute(
            userId:        Auth::id(),
            event:         AuditEventEnum::Updated,
            auditableType: 'governorates',
            auditableId:   $governorate->governorate_id,
            oldValues:     $oldValues,
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
