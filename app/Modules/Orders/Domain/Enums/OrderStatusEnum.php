<?php

namespace App\Modules\Orders\Domain\Enums;

enum OrderStatusEnum: int
{
    case Pending = 1;
    case Assigned = 2;
    case OutForDelivery = 3;
    case AwaitingApproval = 4;
    case Delivered = 5;
    case DeliveredPriceChanged = 6;
    case PartialDelivery = 7;
    case RefusedPaidShipping = 8;
    case RefusedNoPayment = 9;
    case CustomerCancelled = 10;
    case NoAnswer = 11;
    case PhoneOff = 12;
    case CustomerEvading = 13;
    case UnsafeArea = 14;
    case Postponed = 15;
    case OutsideGovernorate = 16;
    case WrongPhone = 17;

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Delivered,
            self::DeliveredPriceChanged,
            self::PartialDelivery,
            self::RefusedPaidShipping,
            self::RefusedNoPayment,
            self::CustomerCancelled,
        ], true);
    }

    public function requiresCollection(): bool
    {
        return in_array($this, [
            self::Delivered,
            self::DeliveredPriceChanged,
            self::PartialDelivery,
            self::RefusedPaidShipping,
        ], true);
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار التوزيع',
            self::Assigned => 'تم التعيين',
            self::OutForDelivery => 'قيد التوصيل',
            self::AwaitingApproval => 'بانتظار الموافقة',
            self::Delivered => 'تم التسليم',
            self::DeliveredPriceChanged => 'تم التسليم بتغيير سعر',
            self::PartialDelivery => 'تسليم جزئي',
            self::RefusedPaidShipping => 'رفض + دفع رسوم الشحن',
            self::RefusedNoPayment => 'رفض وعدم دفع رسوم الشحن',
            self::CustomerCancelled => 'ألغى العميل',
            self::NoAnswer => 'لا يوجد رد',
            self::PhoneOff => 'الهاتف مغلق',
            self::CustomerEvading => 'تهرّب / مختفي',
            self::UnsafeArea => 'منطقة غير آمنة',
            self::Postponed => 'مؤجل',
            self::OutsideGovernorate => 'خارج المحافظة',
            self::WrongPhone => 'رقم هاتف خاطئ',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Assigned, self::OutForDelivery, self::AwaitingApproval => 'blue',
            self::Postponed => 'orange',
            self::Delivered, self::DeliveredPriceChanged, self::PartialDelivery => 'green',
            self::RefusedPaidShipping, self::RefusedNoPayment, self::CustomerCancelled => 'red',
            default => 'gray',
        };
    }

    /** @return list<int> */
    public static function terminalIds(): array
    {
        return array_values(array_map(
            static fn (self $case) => $case->value,
            array_filter(self::cases(), static fn (self $case) => $case->isTerminal()),
        ));
    }

    /** @return list<int> */
    public static function activeIds(): array
    {
        return array_values(array_map(
            static fn (self $case) => $case->value,
            array_filter(self::cases(), static fn (self $case) => ! $case->isTerminal()),
        ));
    }
}
