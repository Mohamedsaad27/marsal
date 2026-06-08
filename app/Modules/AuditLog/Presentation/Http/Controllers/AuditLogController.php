<?php

namespace App\Modules\AuditLog\Presentation\Http\Controllers;

use App\Modules\AuditLog\Application\UseCases\GetAuditLogsKpisUseCase;
use App\Modules\AuditLog\Application\UseCases\GetAuditLogsUseCase;
use App\Modules\AuditLog\Presentation\Http\Requests\IndexAuditLogRequest;
use App\Modules\AuditLog\Presentation\Http\Resources\AuditLogResource;
use App\Modules\Core\Infrastructure\Helpers\PaginationMeta;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AuditLogController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly GetAuditLogsUseCase $getAuditLogs,
        private readonly GetAuditLogsKpisUseCase $getAuditLogsKpis,
    ) {}

    public function index(IndexAuditLogRequest $request): JsonResponse
    {
        $logs = $this->getAuditLogs->execute(
            $request->validated(),
            $request->integer('per_page', 20),
        );

        return $this->auditLogsResponse(
            $logs,
            __('audit_logs::messages.fetched'),
            ['kpis' => $this->getAuditLogsKpis->execute()],
        );
    }

    public function forSubject(string $auditableType, string $auditableId, IndexAuditLogRequest $request): JsonResponse
    {
        $logs = $this->getAuditLogs->forSubject(
            $auditableType,
            $auditableId,
            $request->validated(),
            $request->integer('per_page', 20),
        );

        return $this->auditLogsResponse($logs, __('audit_logs::messages.fetched'));
    }

    private function auditLogsResponse($paginator, string $message, array $extra = []): JsonResponse
    {
        return $this->success(array_merge(
            $extra,
            ['items' => AuditLogResource::collection($paginator->items())],
            PaginationMeta::getMeta($paginator),
        ), $message);
    }
}
