<?php

namespace App\Modules\AuditLog\Application\UseCases;

use App\Modules\AuditLog\Domain\Interfaces\AuditLogRepositoryInterface;

class GetAuditLogsKpisUseCase
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
    ) {}

    /**
     * @return array{
     *     total_logs: int,
     *     today_logs: int,
     *     security_events_today: int
     * }
     */
    public function execute(): array
    {
        return $this->repository->getKpis();
    }
}
