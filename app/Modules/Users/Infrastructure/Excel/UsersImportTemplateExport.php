<?php

namespace App\Modules\Users\Infrastructure\Excel;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersImportTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['name', 'email', 'phone', 'gender', 'role', 'company_name'];
    }

    public function array(): array
    {
        return [
            [
                'خالد مثال',
                'khaled@example.com',
                '01012345678',
                'ذكر',
                'delivery_agent',
                '',
            ],
            [
                'شركة مثال',
                'company@example.com',
                '01098765432',
                '',
                'shipping_company',
                'شركة مثال للشحن',
            ],
        ];
    }
}
