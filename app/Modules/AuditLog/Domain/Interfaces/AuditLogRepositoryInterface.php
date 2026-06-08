<?php

namespace App\Modules\AuditLog\Domain\Interfaces;

use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditLogRepositoryInterface
{
    public function record(
        ?string        $userId,
        int            $actorType,
        AuditEventEnum $event,
        string         $auditableType,
        string         $auditableId,
        array          $oldValues,
        array          $newValues,
        array          $metadata,
        string         $description,
        ?string        $ipAddress,
        ?string        $userAgent,
    ): void;

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function forSubject(
        string $auditableType,
        string $auditableId,
        array $filters = [],
        int $perPage = 20,
    ): LengthAwarePaginator;

    /**
     * @return array{
     *     total_logs: int,
     *     today_logs: int,
     *     security_events_today: int
     * }
     */
    public function getKpis(): array;
}
