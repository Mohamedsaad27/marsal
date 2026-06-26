<?php

namespace App\Modules\Collections\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Collections\Application\DTOs\AdminCollectionFilterDTO;
use App\Modules\Collections\Application\UseCases\Admin\GetAdminCollectionStatsUseCase;
use App\Modules\Collections\Application\UseCases\Admin\ListAdminCollectionsUseCase;
use App\Modules\Collections\Application\UseCases\Admin\MarkCashReceivedUseCase;
use App\Modules\Collections\Presentation\Http\Requests\Admin\ListAdminCollectionsRequest;
use App\Modules\Collections\Presentation\Http\Resources\Admin\AdminCollectionResource;
use App\Modules\Collections\Presentation\Http\Resources\Admin\AdminCollectionStatsResource;
use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class AdminCollectionsController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private GetAdminCollectionStatsUseCase $getStats,
        private ListAdminCollectionsUseCase $listCollections,
        private MarkCashReceivedUseCase $markCashReceived,
    ) {}

    public function stats(): JsonResponse
    {
        $stats = $this->getStats->execute();

        return $this->success(
            new AdminCollectionStatsResource($stats),
            __('collections::messages.collections_stats_success'),
        );
    }

    public function index(ListAdminCollectionsRequest $request): JsonResponse
    {
        $filter = AdminCollectionFilterDTO::fromArray($request->validated());
        $paginator = $this->listCollections->execute($filter);

        return $this->success(
            array_merge(
                ['items' => AdminCollectionResource::collection($paginator->items())],
                PaginationMeta::getMeta($paginator),
            ),
            __('collections::messages.collections_list_success'),
        );
    }

    public function markCashReceived(string $collectionId): JsonResponse
    {
        $collection = $this->markCashReceived->execute($collectionId, (string) auth()->id());

        return $this->success(
            new AdminCollectionResource($collection),
            __('collections::messages.cash_received_success'),
        );
    }
}
