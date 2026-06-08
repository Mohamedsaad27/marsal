<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Locations\Application\DTOs\UpdateCityDTO;
use App\Modules\Locations\Application\Services\LocationCodeGenerator;
use App\Modules\Locations\Domain\Interfaces\CityRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\City;
use Illuminate\Support\Facades\Auth;

class UpdateCityUseCase
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
        private readonly GetCityUseCase $getCityUseCase,
        private readonly GetGovernorateUseCase $getGovernorateUseCase,
        private readonly LocationCodeGenerator $codeGenerator,
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(string $cityId, UpdateCityDTO $dto): City
    {
        $city = $this->getCityUseCase->execute($cityId);
        $oldValues = $this->cityAuditSnapshot($city);
        $governorate = $this->getGovernorateUseCase->execute($dto->governorate_id);

        $governorateCode = $governorate->code ?? 'gov';
        $code = $dto->code;

        if ($code !== $city->code) {
            $code = $this->codeGenerator->forCity($governorateCode, $dto->name_en, $code);
        }

        $city = $this->cityRepository->update($city, [
            'governorate_id' => $dto->governorate_id,
            'name_ar' => $dto->name_ar,
            'name_en' => $dto->name_en,
            'code' => $code,
            'is_active' => $dto->is_active,
        ]);

        $this->recordAudit->execute(
            userId:        Auth::id(),
            event:         AuditEventEnum::Updated,
            auditableType: 'cities',
            auditableId:   $city->city_id,
            oldValues:     $oldValues,
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
