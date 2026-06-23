<?php

namespace App\Modules\Users\Domain\Enums;

enum VehicleTypeEnum: int
{
    case Motorcycle = 1;
    case Car = 2;

    public function labelAr(): string
    {
        return match ($this) {
            self::Motorcycle => 'دراجة نارية',
            self::Car => 'سيارة',
        };
    }
}
