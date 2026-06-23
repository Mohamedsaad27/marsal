<?php

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Orders\Application\UseCases\Admin\GetApprovalDetailUseCase;
use App\Modules\Orders\Application\UseCases\Admin\GetApprovalStatsUseCase;
use App\Modules\Orders\Application\UseCases\Admin\ListApprovalRequestsUseCase;
use App\Modules\Orders\Application\UseCases\Admin\ReviewApprovalRequestUseCase;
use App\Modules\Orders\Presentation\Http\Requests\Admin\ListApprovalsRequest;
use App\Modules\Orders\Presentation\Http\Requests\Admin\ReviewApprovalRequest;
use App\Modules\Orders\Presentation\Http\Resources\Admin\ApprovalRequestResource;
use App\Modules\Orders\Presentation\Http\Resources\Admin\ApprovalStatsResource;
use Illuminate\Http\JsonResponse;

class AdminApprovalController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private GetApprovalStatsUseCase $getStats,
        private ListApprovalRequestsUseCase $listApprovals,
        private GetApprovalDetailUseCase $getDetail,
        private ReviewApprovalRequestUseCase $reviewApproval,
    ) {}

    public function stats(): JsonResponse
    {
        $stats = $this->getStats->execute();

        return $this->success(new ApprovalStatsResource($stats), __('orders::messages.stats_success'));
    }

    public function index(ListApprovalsRequest $request): JsonResponse
    {
        $perPage   = min((int) $request->query('per_page', 20), 100);
        $paginator = $this->listApprovals->execute(
            status:  $request->integer('status') ?: null,
            type:    $request->integer('type') ?: null,
            agentId: $request->query('agent_id'),
            perPage: $perPage,
        );

        return $this->success(
            array_merge(
                ['items' => ApprovalRequestResource::collection($paginator->items())],
                PaginationMeta::getMeta($paginator),
            ),
            __('orders::messages.approvals_list_success'),
        );
    }

    public function show(string $approvalRequestId): JsonResponse
    {
        $record = $this->getDetail->execute($approvalRequestId);

        return $this->success(
            new ApprovalRequestResource($record),
            __('orders::messages.approval_detail_success'),
        );
    }

    public function review(ReviewApprovalRequest $request, string $approvalRequestId): JsonResponse
    {
        $record = $this->reviewApproval->execute(
            approvalRequestId: $approvalRequestId,
            action:            $request->validated('action'),
            adminUserId:       $request->user()->user_id,
            reviewNotes:       $request->validated('review_notes'),
        );

        return $this->success(
            new ApprovalRequestResource($record),
            __('orders::messages.approval_reviewed'),
        );
    }
}
