<?php

namespace App\Modules\AuditLog\Domain\Enums;

enum AuditDatePeriodEnum: string
{
    case Today     = 'today';
    case ThisWeek  = 'this_week';
    case ThisMonth = 'this_month';
    case Custom    = 'custom';

    public function labelAr(): string
    {
        return match ($this) {
            self::Today     => 'اليوم',
            self::ThisWeek  => 'هذا الأسبوع',
            self::ThisMonth => 'هذا الشهر',
            self::Custom    => 'فترة محددة',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Today     => 'Today',
            self::ThisWeek  => 'This week',
            self::ThisMonth => 'This month',
            self::Custom    => 'Custom range',
        };
    }
}
