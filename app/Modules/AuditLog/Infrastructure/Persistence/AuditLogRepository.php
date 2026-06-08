<?php

namespace App\Modules\AuditLog\Infrastructure\Persistence;

use App\Modules\AuditLog\Domain\Enums\AuditEventEnum;
use App\Modules\AuditLog\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Modules\AuditLog\Infrastructure\Database\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogRepository implements AuditLogRepositoryInterface
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
    ): void {
        AuditLog::create([
            'user_id'        => $userId,
            'actor_type'     => $actorType,
            'event'          => $event->value,
            'auditable_type' => $auditableType,
            'auditable_id'   => $auditableId,
            'old_values'     => $oldValues ?: null,
            'new_values'     => $newValues ?: null,
            'metadata'       => $metadata ?: null,
            'description'    => $description,
            'ip_address'     => $ipAddress,
            'user_agent'     => $userAgent,
            'created_at'     => now(),
        ]);
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return AuditLog::with('actor')
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['event'] ?? null, fn ($q, $v) => $q->where('event', $v))
            ->when($filters['auditable_type'] ?? null, fn ($q, $v) => $q->where('auditable_type', $v))
            ->when($filters['auditable_id'] ?? null, fn ($q, $v) => $q->where('auditable_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function forSubject(
        string $auditableType,
        string $auditableId,
        array $filters = [],
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->paginate(
            array_merge($filters, [
                'auditable_type' => $auditableType,
                'auditable_id'   => $auditableId,
            ]),
            $perPage,
        );
    }

    public function getKpis(): array
    {
        $todayStart = now()->startOfDay();

        $securityEvents = [
            AuditEventEnum::Login->value,
            AuditEventEnum::Logout->value,
            AuditEventEnum::PasswordChanged->value,
        ];

        return [
            'total_logs'            => AuditLog::query()->count(),
            'today_logs'            => AuditLog::query()->where('created_at', '>=', $todayStart)->count(),
            'security_events_today' => AuditLog::query()
                ->where('created_at', '>=', $todayStart)
                ->whereIn('event', $securityEvents)
                ->count(),
        ];
    }
}
