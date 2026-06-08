<?php

namespace App\Modules\AuditLog\Domain\Enums;

enum AuditActorTypeEnum: int
{
    case SuperAdmin      = 1;
    case ShippingCompany = 2;
    case DeliveryAgent   = 3;
    case System          = 4;

    public function labelAr(): string
    {
        return match ($this) {
            self::SuperAdmin => 'مدير نظام',
            self::ShippingCompany => 'شركة شحن',
            self::DeliveryAgent => 'مندوب توصيل',
            self::System => 'نظام',
        };
    }
    public function labelEn(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::ShippingCompany => 'Shipping Company',
            self::DeliveryAgent => 'Delivery Agent',
            self::System => 'System',
        };
    }
}
