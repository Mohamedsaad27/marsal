<?php

namespace App\Modules\Locations\Presentation\Http\Controllers\Admin;

use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Locations\Application\DTOs\CreateGovernorateDTO;
use App\Modules\Locations\Application\DTOs\UpdateGovernorateDTO;
use App\Modules\Locations\Application\UseCases\CreateGovernorateUseCase;
use App\Modules\Locations\Application\UseCases\DeleteGovernorateUseCase;
use App\Modules\Locations\Application\UseCases\GetGovernorateUseCase;
use App\Modules\Locations\Application\UseCases\ListCitiesUseCase;
use App\Modules\Locations\Application\UseCases\ListGovernoratesUseCase;
use App\Modules\Locations\Application\UseCases\UpdateGovernorateUseCase;
use App\Modules\Locations\Presentation\Http\Controllers\Concerns\PaginatesResources;
use App\Modules\Locations\Presentation\Http\Controllers\Concerns\ParsesLocationListFilters;
use App\Modules\Locations\Presentation\Http\Requests\StoreGovernorateRequest;
use App\Modules\Locations\Presentation\Http\Requests\UpdateGovernorateRequest;
use App\Modules\Locations\Presentation\Http\Resources\CityResource;
use App\Modules\Locations\Presentation\Http\Resources\GovernorateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GovernorateController extends Controller
{
    use ApiResponseTrait;
    use PaginatesResources;
    use ParsesLocationListFilters;

    public function __construct(
        private readonly ListGovernoratesUseCase $listGovernoratesUseCase,
        private readonly GetGovernorateUseCase $getGovernorateUseCase,
        private readonly CreateGovernorateUseCase $createGovernorateUseCase,
        private readonly UpdateGovernorateUseCase $updateGovernorateUseCase,
        private readonly DeleteGovernorateUseCase $deleteGovernorateUseCase,
        private readonly ListCitiesUseCase $listCitiesUseCase,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->listGovernoratesUseCase->execute(
            $this->locationListFilters($request),
            (int) $request->integer('per_page', 15),
        );

        return $this->paginatedSuccess($paginator, GovernorateResource::class);
    }

    public function show(string $governorateId): JsonResponse
    {
        $governorate = $this->getGovernorateUseCase->execute($governorateId);

        return $this->success(new GovernorateResource($governorate));
    }

    public function store(StoreGovernorateRequest $request): JsonResponse
    {
        $governorate = $this->createGovernorateUseCase->execute(
            CreateGovernorateDTO::fromArray($request->validated()),
        );

        return $this->success(
            new GovernorateResource($governorate),
            __('locations::messages.governorate_created'),
            201,
        );
    }

    public function update(UpdateGovernorateRequest $request, string $governorateId): JsonResponse
    {
        $governorate = $this->updateGovernorateUseCase->execute(
            $governorateId,
            UpdateGovernorateDTO::fromArray($request->validated()),
        );

        return $this->success(
            new GovernorateResource($governorate),
            __('locations::messages.governorate_updated'),
        );
    }

    public function destroy(string $governorateId): JsonResponse
    {
        $this->deleteGovernorateUseCase->execute($governorateId);

        return $this->success(null, __('locations::messages.governorate_deleted'));
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
