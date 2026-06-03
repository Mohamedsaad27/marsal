<?php

namespace App\Modules\Locations\Presentation\Http\Controllers\Admin;

use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Locations\Application\DTOs\CreateCityDTO;
use App\Modules\Locations\Application\DTOs\UpdateCityDTO;
use App\Modules\Locations\Application\UseCases\CreateCityUseCase;
use App\Modules\Locations\Application\UseCases\DeleteCityUseCase;
use App\Modules\Locations\Application\UseCases\GetCitiesKpisUseCase;
use App\Modules\Locations\Application\UseCases\GetCityUseCase;
use App\Modules\Locations\Application\UseCases\ListCitiesUseCase;
use App\Modules\Locations\Application\UseCases\ToggleCityStatusUseCase;
use App\Modules\Locations\Application\UseCases\UpdateCityUseCase;
use App\Modules\Locations\Presentation\Http\Controllers\Concerns\PaginatesResources;
use App\Modules\Locations\Presentation\Http\Controllers\Concerns\ParsesLocationListFilters;
use App\Modules\Locations\Presentation\Http\Requests\StoreCityRequest;
use App\Modules\Locations\Presentation\Http\Requests\UpdateCityRequest;
use App\Modules\Locations\Presentation\Http\Resources\CityResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CityController extends Controller
{
    use ApiResponseTrait;
    use PaginatesResources;
    use ParsesLocationListFilters;

    public function __construct(
        private readonly ListCitiesUseCase $listCitiesUseCase,
        private readonly GetCitiesKpisUseCase $getCitiesKpisUseCase,
        private readonly GetCityUseCase $getCityUseCase,
        private readonly CreateCityUseCase $createCityUseCase,
        private readonly UpdateCityUseCase $updateCityUseCase,
        private readonly ToggleCityStatusUseCase $toggleCityStatusUseCase,
        private readonly DeleteCityUseCase $deleteCityUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->listCitiesUseCase->execute(
            $this->locationListFilters($request),
            (int) $request->integer('per_page', 15),
        );

        return $this->paginatedSuccess(
            $paginator,
            CityResource::class,
            ['kpis' => $this->getCitiesKpisUseCase->execute()],
        );
    }

    public function show(string $cityId): JsonResponse
    {
        $city = $this->getCityUseCase->execute($cityId);

        return $this->success(new CityResource($city));
    }

    public function store(StoreCityRequest $request): JsonResponse
    {
        $city = $this->createCityUseCase->execute(
            CreateCityDTO::fromArray($request->validated()),
        );

        return $this->success(
            new CityResource($city),
            __('locations::messages.city_created'),
            201,
        );
    }

    public function update(UpdateCityRequest $request, string $cityId): JsonResponse
    {
        $city = $this->updateCityUseCase->execute(
            $cityId,
            UpdateCityDTO::fromArray($request->validated()),
        );

        return $this->success(
            new CityResource($city),
            __('locations::messages.city_updated'),
        );
    }

    public function toggleStatus(string $cityId): JsonResponse
    {
        $city = $this->toggleCityStatusUseCase->execute($cityId);

        return $this->success(
            new CityResource($city),
            __('locations::messages.city_status_toggled'),
        );
    }

    public function destroy(string $cityId): JsonResponse
    {
        $this->deleteCityUseCase->execute($cityId);

        return $this->success(null, __('locations::messages.city_deleted'));
    }
}
