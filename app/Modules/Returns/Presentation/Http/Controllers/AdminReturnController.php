<?php

namespace App\Modules\Returns\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Returns\Application\UseCases\Admin\GetReturnStatsUseCase;
use App\Modules\Returns\Application\UseCases\Admin\ListReturnsUseCase;
use App\Modules\Returns\Application\UseCases\Admin\ReceiveReturnUseCase;
use App\Modules\Returns\Application\UseCases\Admin\ReturnToCompanyUseCase;
use App\Modules\Returns\Presentation\Http\Requests\Admin\ListReturnsRequest;
use App\Modules\Returns\Presentation\Http\Resources\Admin\ReturnResource;
use App\Modules\Returns\Presentation\Http\Resources\Admin\ReturnStatsResource;
use Illuminate\Http\JsonResponse;

class AdminReturnController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private GetReturnStatsUseCase $getStats,
        private ListReturnsUseCase $listReturns,
        private ReceiveReturnUseCase $receiveReturn,
        private ReturnToCompanyUseCase $returnToCompany,
    ) {}

    public function stats(): JsonResponse
    {
        $stats = $this->getStats->execute();

        return $this->success(new ReturnStatsResource($stats), __('returns::messages.stats_success'));
    }

    public function index(ListReturnsRequest $request): JsonResponse
    {
        $perPage   = min((int) $request->query('per_page', 20), 100);
        $paginator = $this->listReturns->execute(
            status:    $request->integer('status') ?: null,
            companyId: $request->query('company_id'),
            agentId:   $request->query('agent_id'),
            perPage:   $perPage,
        );

        return $this->success(
            array_merge(
                ['items' => ReturnResource::collection($paginator->items())],
                PaginationMeta::getMeta($paginator),
            ),
            __('returns::messages.list_success'),
        );
    }

    public function receive(string $returnId): JsonResponse
    {
        $record = $this->receiveReturn->execute($returnId);

        return $this->success(
            new ReturnResource($record),
            __('returns::messages.received_success'),
        );
    }

    public function returnToCompany(string $returnId): JsonResponse
    {
        $record = $this->returnToCompany->execute($returnId);

        return $this->success(
            new ReturnResource($record),
            __('returns::messages.sent_to_company_success'),
        );
    }
}
