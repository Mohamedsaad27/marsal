<?php

namespace App\Modules\Locations\Application\UseCases;

use App\Modules\AuditLog\Application\UseCases\RecordAuditUseCase;
use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\Locations\Application\Exceptions\LocationInUseException;
use App\Modules\Locations\Domain\Interfaces\CityRepositoryInterface;
use App\Modules\Locations\Infrastructure\Database\Models\City;
use Illuminate\Support\Facades\Auth;

class DeleteCityUseCase
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
        private readonly GetCityUseCase $getCityUseCase,
        private readonly RecordAuditUseCase $recordAudit,
    ) {}

    public function execute(string $cityId): void
    {
        $city = $this->getCityUseCase->execute($cityId);

        if ($this->cityRepository->isCityReferenced($cityId)) {
            throw new LocationInUseException;
        }

        $this->recordAudit->execute(
            userId:        Auth::id(),
            event:         AuditEventEnum::Deleted,
            auditableType: 'cities',
            auditableId:   $city->city_id,
            oldValues:     $this->cityAuditSnapshot($city),
        );

        $this->cityRepository->delete($city);
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
