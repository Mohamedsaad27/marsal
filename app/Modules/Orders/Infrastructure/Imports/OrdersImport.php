<?php

namespace App\Modules\Orders\Infrastructure\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;

class OrdersImport implements ToCollection, WithStartRow
{
    private Collection $rows;

    public function startRow(): int
    {
        // Row 1 = Arabic headers → skip it, start reading from row 2
        return 2;
    }

    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }

    public function getRows(): Collection
    {
        return $this->rows ?? collect();
    }
}
