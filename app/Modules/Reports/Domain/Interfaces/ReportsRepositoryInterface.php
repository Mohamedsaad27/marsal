<?php

namespace App\Modules\Reports\Domain\Interfaces;

use App\Modules\Reports\Application\DTOs\ReportFilterDTO;

interface ReportsRepositoryInterface
{
    public function orders(ReportFilterDTO $filter): array;

    public function collections(ReportFilterDTO $filter): array;

    public function settlements(ReportFilterDTO $filter): array;

    public function deliveryAgents(ReportFilterDTO $filter): array;

    public function shippingCompanies(ReportFilterDTO $filter): array;
}
