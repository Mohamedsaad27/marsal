<?php

namespace App\Modules\Orders\Domain\Enums;

enum OrderProofFileTypeEnum: int
{
    case Image = 1;
    case Pdf = 2;
    case Other = 3;

    public function labelAr(): string
    {
        return match ($this) {
            self::Image => 'صورة',
            self::Pdf => 'ملف PDF',
            self::Other => 'أخرى',
        };
    }
}
