<?php

namespace App\Modules\Notifications\Domain\Enums;

enum NotificationTypeEnum: int
{
    case NewOrder          = 1;
    case StatusChange      = 2;
    case ApprovalRequest   = 3;
    case TimerStart        = 4;
    case TimerExpired      = 5;
    case NewMessage        = 6;
    case PhoneUpdated      = 7;
    case PostponedReminder = 8;
    case Collected         = 9;
    case Settled           = 10;
    case Returned          = 11;
    case OrderReassigned   = 12;

    /**
     * Human-readable Arabic label for this notification type.
     */
    public function labelAr(): string
    {
        return match ($this) {
            self::NewOrder          => 'طلب توصيل جديد',
            self::StatusChange      => 'تحديث حالة الطلب',
            self::ApprovalRequest   => 'طلب موافقة على تغيير السعر',
            self::TimerStart        => 'بدأ توقيت رفض الاستلام',
            self::TimerExpired      => 'انتهى وقت رفض الاستلام',
            self::NewMessage        => 'رسالة جديدة',
            self::PhoneUpdated      => 'تم تحديث رقم الهاتف',
            self::PostponedReminder => 'تذكير بموعد تأجيل التسليم',
            self::Collected         => 'تحصيل نقدي من المندوب',
            self::Settled           => 'تسوية مالية',
            self::Returned          => 'مرتجع',
            self::OrderReassigned   => 'إعادة تعيين طلب',
        };
    }

    /**
     * The role code that typically receives this notification type.
     * Used for documentation/routing purposes.
     * timer_expired notifies both agent and company — handled in the listener.
     */
    public function primaryRecipientRole(): string
    {
        return match ($this) {
            self::NewOrder          => 'delivery_agent',
            self::StatusChange      => 'shipping_company',
            self::ApprovalRequest   => 'shipping_company',
            self::TimerStart        => 'shipping_company',
            self::TimerExpired      => 'shipping_company', // also delivery_agent — listener handles both
            self::NewMessage        => 'any',
            self::PhoneUpdated      => 'delivery_agent',
            self::PostponedReminder => 'delivery_agent',
            self::Collected,
            self::Settled,
            self::Returned,
            self::OrderReassigned   => 'super_admin',
        };
    }

    /**
     * Dashboard KPI bucket for filtering unread notification counts.
     */
    public function kpiCategory(): NotificationKpiCategoryEnum
    {
        return match ($this) {
            self::NewOrder,
            self::StatusChange,
            self::PostponedReminder,
            self::PhoneUpdated      => NotificationKpiCategoryEnum::Shipments,
            self::ApprovalRequest,
            self::TimerStart,
            self::TimerExpired      => NotificationKpiCategoryEnum::Approvals,
            self::Collected,
            self::Settled,
            self::NewMessage        => NotificationKpiCategoryEnum::Collections,
            self::Returned,
            self::OrderReassigned   => NotificationKpiCategoryEnum::Shipments,
        };
    }
}
