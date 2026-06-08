<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Locations\Application\DTOs\CreateCityDTO;
use App\Modules\Locations\Application\Services\LocationCodeGenerator;
use App\Modules\Locations\Domain\Interfaces\CityRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\City;
use Illuminate\Support\Facades\Auth;

class CreateCityUseCase
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
        private readonly GetGovernorateUseCase $getGovernorateUseCase,
        private readonly LocationCodeGenerator $codeGenerator,
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(CreateCityDTO $dto): City
    {
        $governorate = $this->getGovernorateUseCase->execute($dto->governorate_id);

        if (! $governorate->is_active) {
            throw new \InvalidArgumentException(__('locations::messages.governorate_inactive'));
        }

        $governorateCode = $governorate->code ?? 'gov';

        $code = $dto->code
            ? $this->codeGenerator->forCity($governorateCode, $dto->name_en, $dto->code)
            : $this->codeGenerator->forCity($governorateCode, $dto->name_en);

        $city = $this->cityRepository->create([
            'governorate_id' => $dto->governorate_id,
            'name_ar' => $dto->name_ar,
            'name_en' => $dto->name_en,
            'code' => $code,
            'is_active' => $dto->is_active,
        ]);

        $this->recordAudit->execute(
            userId:        Auth::id(),
            event:         AuditEventEnum::Created,
            auditableType: 'cities',
            auditableId:   $city->city_id,
            newValues:     $this->cityAuditSnapshot($city),
        );

        return $city;
    }

    private function cityAuditSnapshot(City $city): array
    {
        return [
            'governorate_id' => $city->governorate_id,
            'name_ar'        => $city->name_ar,
            'name_en'        => $city->name_en,
            'code'           => $city->code,
            'is_active'      => $city->is_active,
        ];
    }
}
