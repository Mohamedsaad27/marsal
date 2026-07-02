<?php

namespace App\Modules\Orders\Application\UseCases\Admin;

use App\Modules\Orders\Application\DTOs\AdminOrderExportFilterDTO;
use App\Modules\Orders\Application\DTOs\ExportOrdersResultDTO;
use App\Modules\Orders\Application\Services\OrderExportRowMapper;
use App\Modules\Orders\Domain\Interfaces\AdminOrderRepositoryInterface;
use App\Modules\Orders\Infrastructure\Excel\OrdersExport;
use App\Modules\Users\Infrastructure\Database\Models\ShippingCompany;
use Illuminate\Support\Str;

class ExportOrdersUseCase
{
    public function __construct(
        private AdminOrderRepositoryInterface $repository,
        private OrderExportRowMapper $rowMapper,
    ) {}

    public function execute(AdminOrderExportFilterDTO $filter): ExportOrdersResultDTO
    {
        $companyName = $this->resolveCompanyLabel($filter->shippingCompanyId);
        $filename    = $this->buildFilename($companyName);
        $orders      = $this->repository->lazyForExport($filter);

        return new ExportOrdersResultDTO(
            filename: $filename,
            export:   new OrdersExport($orders, $this->rowMapper),
        );
    }

    private function resolveCompanyLabel(?string $shippingCompanyId): string
    {
        if ($shippingCompanyId === null) {
            return 'كل الشركات';
        }

        $name = ShippingCompany::query()
            ->where('shipping_company_id', $shippingCompanyId)
            ->value('company_name');

        return $name ?: 'شركة';
    }

    private function buildFilename(string $companyName): string
    {
        $date = now()->format('Y-m-d');
        $safe = Str::of($companyName)
            ->replaceMatches('/[\\\\\/:*?"<>|]/', '-')
            ->trim()
            ->toString();

        return "{$date} - {$safe}.xlsx";
    }
}
