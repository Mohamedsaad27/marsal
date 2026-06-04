<?php

namespace App\Modules\Users\Infrastructure\Excel;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

class UsersImport implements ToCollection, WithHeadingRow, WithLimit
{
    private Collection $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    public function limit(): int
    {
        return 500;
    }

    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }

    public function getRows(): Collection
    {
        return $this->rows;
    }
}
