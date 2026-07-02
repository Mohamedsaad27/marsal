<?php

namespace App\Modules\Orders\Infrastructure\Excel;

use App\Modules\Orders\Application\Services\OrderExportRowMapper;
use App\Modules\Orders\Domain\Services\OrdersExcelSchema;
use App\Modules\Orders\Infrastructure\Database\Models\Order;
use Generator;
use Illuminate\Support\LazyCollection;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrdersExport implements FromGenerator, WithHeadings
{
    /**
     * @param LazyCollection<int, Order> $orders
     */
    public function __construct(
        private LazyCollection $orders,
        private OrderExportRowMapper $rowMapper,
    ) {}

    public function headings(): array
    {
        return OrdersExcelSchema::EXPORT_HEADINGS;
    }

    public function generator(): Generator
    {
        foreach ($this->orders as $order) {
            yield $this->rowMapper->map($order);
        }
    }
}
