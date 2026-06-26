<?php

namespace App\Modules\Collections\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Collections\Application\DTOs\CreateSettlementDTO;
use App\Modules\Collections\Application\DTOs\SettlementFilterDTO;
use App\Modules\Collections\Application\UseCases\Admin\ApproveSettlementUseCase;
use App\Modules\Collections\Application\UseCases\Admin\CreateSettlementUseCase;
use App\Modules\Collections\Application\UseCases\Admin\GetSettlementStatsUseCase;
use App\Modules\Collections\Application\UseCases\Admin\ListSettlementsUseCase;
use App\Modules\Collections\Application\UseCases\Admin\MarkSettlementPaidUseCase;
use App\Modules\Collections\Presentation\Http\Requests\Admin\CreateSettlementRequest;
use App\Modules\Collections\Presentation\Http\Requests\Admin\ListSettlementsRequest;
use App\Modules\Collections\Presentation\Http\Requests\Admin\MarkSettlementPaidRequest;
use App\Modules\Collections\Presentation\Http\Resources\Admin\SettlementResource;
use App\Modules\Collections\Presentation\Http\Resources\Admin\SettlementStatsResource;
use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class AdminSettlementsController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private GetSettlementStatsUseCase $getStats,
        private ListSettlementsUseCase $listSettlements,
        private CreateSettlementUseCase $createSettlement,
        private ApproveSettlementUseCase $approveSettlement,
        private MarkSettlementPaidUseCase $markSettlementPaid,
    ) {}

    public function stats(): JsonResponse
    {
        $stats = $this->getStats->execute();

        return $this->success(
            new SettlementStatsResource($stats),
            __('collections::messages.settlements_stats_success'),
        );
    }

    public function index(ListSettlementsRequest $request): JsonResponse
    {
        $filter = SettlementFilterDTO::fromArray($request->validated());
        $paginator = $this->listSettlements->execute($filter);

        return $this->success(
            array_merge(
                ['items' => SettlementResource::collection($paginator->items())],
                PaginationMeta::getMeta($paginator),
            ),
            __('collections::messages.settlements_list_success'),
        );
    }

    public function store(CreateSettlementRequest $request): JsonResponse
    {
        $dto = CreateSettlementDTO::fromArray($request->validated(), (string) auth()->id());
        $settlement = $this->createSettlement->execute($dto);

        return $this->success(
            new SettlementResource($settlement),
            __('collections::messages.settlement_created'),
            201,
        );
    }

    public function approve(string $settlementId): JsonResponse
    {
        $settlement = $this->approveSettlement->execute($settlementId);

        return $this->success(
            new SettlementResource($settlement),
            __('collections::messages.settlement_approved'),
        );
    }

    public function markPaid(string $settlementId, MarkSettlementPaidRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $settlement = $this->markSettlementPaid->execute(
            settlementId: $settlementId,
            paymentMethod: $validated['payment_method'],
            paymentReference: $validated['payment_reference'] ?? null,
            notes: $validated['notes'] ?? null,
        );

        return $this->success(
            new SettlementResource($settlement),
            __('collections::messages.settlement_paid'),
        );
    }
}
