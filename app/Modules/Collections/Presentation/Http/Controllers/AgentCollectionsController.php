<?php

namespace App\Modules\Collections\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Collections\Application\UseCases\Agent\GetAgentCollectionsSummaryUseCase;
use App\Modules\Collections\Application\UseCases\Agent\ListAgentCollectionsUseCase;
use App\Modules\Collections\Presentation\Http\Resources\AgentCollectionListResource;
use App\Modules\Collections\Presentation\Http\Resources\AgentCollectionsSummaryResource;
use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentCollectionsController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private ListAgentCollectionsUseCase $listCollections,
        private GetAgentCollectionsSummaryUseCase $getSummary,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $settled = filter_var($request->query('settled', false), FILTER_VALIDATE_BOOLEAN);
        $perPage = min((int) $request->query('per_page', 20), 100);

        $paginator = $this->listCollections->execute(
            userId: $request->user()->user_id,
            settled: $settled,
            perPage: $perPage,
        );

        return $this->success(
            array_merge(
                ['items' => AgentCollectionListResource::collection($paginator->items())],
                PaginationMeta::getMeta($paginator),
            ),
            __('collections::messages.list_success'),
        );
    }

    public function summary(Request $request): JsonResponse
    {
        $data = $this->getSummary->execute($request->user()->user_id);

        return $this->success(
            new AgentCollectionsSummaryResource($data),
            __('collections::messages.summary_success'),
        );
    }
}
