<?php

namespace App\Modules\Reports\Application\UseCases;

use App\Modules\Reports\Application\DTOs\ReportFilterDTO;
use App\Modules\Reports\Domain\Interfaces\ReportsRepositoryInterface;

class GetOrdersReportUseCase
{
    public function __construct(private ReportsRepositoryInterface $repository) {}

    public function execute(ReportFilterDTO $filter): array
    {
        return $this->repository->orders($filter);
    }
}
