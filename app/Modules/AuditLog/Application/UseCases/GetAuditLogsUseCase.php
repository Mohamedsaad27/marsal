<?php

namespace App\Modules\AuditLog\Application\UseCases;

use App\Modules\AuditLog\Application\Services\AuditDateRangeResolver;
use App\Modules\AuditLog\Domain\Interfaces\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetAuditLogsUseCase
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
        private readonly AuditDateRangeResolver $dateRangeResolver,
    ) {}

    public function execute(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate(
            $this->applyDateFilters($filters),
            $perPage,
        );
    }

    public function forSubject(
        string $auditableType,
        string $auditableId,
        array $filters = [],
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->repository->forSubject(
            $auditableType,
            $auditableId,
            $this->applyDateFilters($filters),
            $perPage,
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function applyDateFilters(array $filters): array
    {
        $range = $this->dateRangeResolver->resolve(
            $filters['date_period'] ?? null,
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
        );

        unset($filters['date_period']);

        if ($range['date_from'] !== null) {
            $filters['date_from'] = $range['date_from'];
        } else {
            unset($filters['date_from']);
        }

        if ($range['date_to'] !== null) {
            $filters['date_to'] = $range['date_to'];
        } else {
            unset($filters['date_to']);
        }

        return $filters;
    }
}
