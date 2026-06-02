<?php

namespace App\Modules\Locations\Presentation\Http\Controllers;

use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Locations\Application\UseCases\GetGovernorateUseCase;
use App\Modules\Locations\Application\UseCases\ListCitiesUseCase;
use App\Modules\Locations\Application\UseCases\ListGovernoratesUseCase;
use App\Modules\Locations\Presentation\Http\Controllers\Concerns\PaginatesResources;
use App\Modules\Locations\Presentation\Http\Controllers\Concerns\ParsesLocationListFilters;
use App\Modules\Locations\Presentation\Http\Resources\CityResource;
use App\Modules\Locations\Presentation\Http\Resources\GovernorateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LocationReferenceController extends Controller
{
    use ApiResponseTrait;
    use PaginatesResources;
    use ParsesLocationListFilters;

    public function __construct(
        private readonly ListGovernoratesUseCase $listGovernoratesUseCase,
        private readonly ListCitiesUseCase $listCitiesUseCase,
        private readonly GetGovernorateUseCase $getGovernorateUseCase,
    ) {}

    public function governorates(Request $request): JsonResponse
    {
        $paginator = $this->listGovernoratesUseCase->execute(
            $this->locationListFilters($request),
            (int) $request->integer('per_page', 15),
        );

        return $this->paginatedSuccess($paginator, GovernorateResource::class);
    }

    public function cities(Request $request, string $governorateId): JsonResponse
    {
        $this->getGovernorateUseCase->execute($governorateId);

        $paginator = $this->listCitiesUseCase->execute(
            $this->locationListFilters($request, $governorateId),
            (int) $request->integer('per_page', 15),
        );

        return $this->paginatedSuccess($paginator, CityResource::class);
    }
}
