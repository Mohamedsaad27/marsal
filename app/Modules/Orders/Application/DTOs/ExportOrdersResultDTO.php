<?php

namespace App\Modules\Orders\Application\DTOs;

use App\Modules\Orders\Infrastructure\Excel\OrdersExport;

readonly class ExportOrdersResultDTO
{
    public function __construct(
        public string $filename,
        public OrdersExport $export,
    ) {}
}
