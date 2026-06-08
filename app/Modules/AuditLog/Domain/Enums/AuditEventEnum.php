<?php

namespace App\Modules\AuditLog\Domain\Enums;

enum AuditEventEnum: int
{
    case Created         = 1;
    case Updated         = 2;
    case Deleted         = 3;
    case Restored        = 4;
    case Login           = 5;
    case Logout          = 6;
    case StatusChanged   = 7;
    case Assigned        = 8;
    case Approved        = 9;
    case Rejected        = 10;
    case Settled         = 11;
    case Collected       = 12;
    case Returned        = 13;
    case Exported        = 14;
    case PasswordChanged = 15;
    case Activated       = 16;
    case Deactivated     = 17;

    public function labelAr(): string
    {
        return match ($this) {
            self::Created         => 'تم الإنشاء',
            self::Updated         => 'تم التعديل',
            self::Deleted         => 'تم الحذف',
            self::Restored        => 'تم الاسترجاع',
            self::Login           => 'تسجيل دخول',
            self::Logout          => 'تسجيل خروج',
            self::StatusChanged   => 'تغيير الحالة',
            self::Assigned        => 'تم التعيين',
            self::Approved        => 'تمت الموافقة',
            self::Rejected        => 'تم الرفض',
            self::Settled         => 'تمت التسوية',
            self::Collected       => 'تم التحصيل',
            self::Returned        => 'تم الإرجاع',
            self::Exported        => 'تم التصدير',
            self::PasswordChanged => 'تغيير كلمة المرور',
            self::Activated       => 'تم التفعيل',
            self::Deactivated     => 'تم التعطيل',
        };
    }
}
