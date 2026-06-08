<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Locations\Domain\Interfaces\CityRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\City;
use Illuminate\Support\Facades\Auth;

class ToggleCityStatusUseCase
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
        private readonly GetCityUseCase $getCityUseCase,
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(string $cityId): City
    {
        $city = $this->getCityUseCase->execute($cityId);
        $wasActive = $city->is_active;

        $city = $this->cityRepository->toggleStatus($city);

        $this->recordAudit->execute(
            userId:        Auth::id(),
            event:         $wasActive ? AuditEventEnum::Deactivated : AuditEventEnum::Activated,
            auditableType: 'cities',
            auditableId:   $city->city_id,
            oldValues:     ['is_active' => $wasActive],
            newValues:     ['is_active' => $city->is_active],
            metadata:      [
                'name_en'        => $city->name_en,
                'governorate_id' => $city->governorate_id,
            ],
        );

        return $city;
    }
}
