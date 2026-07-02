<?php

namespace App\Modules\Orders\Domain\Enums;

enum ImportStatusHintEnum: string
{
    // ── §٧.١ حالات التوصيل مع تحصيل ─────────────────────────────────────
    case Delivered             = 'تم التوصيل';            // تحصيل كامل بالمبلغ الأصلي
    case DeliveredPriceChanged = 'تم التوصيل بتغيير سعر'; // تحصيل بمبلغ معدّل معتمد
    case PartialDelivery       = 'تسليم جزئي';            // تحصيل جزئي
    case RefusedPaidShipping   = 'رفض + دفع الشحن';       // رفض مع دفع رسوم الشحن

    // ── §٧.٢ حالات بدون تحصيل ────────────────────────────────────────────
    case RefusedNoPayment      = 'رفض وعدم دفع الشحن';    // رفض كلي بدون أي مبلغ
    case CustomerCancelled     = 'ألغى العميل';            // ألغى العميل الطلب مسبقاً
    case NoAnswer              = 'لا يوجد رد';             // العميل لا يرد على الهاتف
    case PhoneOff              = 'الهاتف مغلق';            // هاتف العميل مغلق
    case Postponed             = 'مؤجل';                   // بطلب من العميل — يتطلب تاريخ

    // ── §٧.٣ حالات ميدانية بدون تحصيل ──────────────────────────────────────
    case UnsafeArea            = 'منطقة غير آمنة';         // المنطقة غير آمنة
    case OutsideGovernorate    = 'خارج المحافظة';           // خارج نطاق التوصيل
    case WrongPhone            = 'رقم هاتف خاطئ';          // رقم هاتف خاطئ

    // ── §٧.٤ حالات وسيطة (من الشيت — قيد التشغيل) ───────────────────────
    case OutForDelivery        = 'قيد التوصيل';            // خرج للتوصيل
    case Assigned              = 'معيّن لمندوب';           // تم التعيين لم يبدأ بعد

    /**
     * Legacy / alternate Arabic labels found in older Excel templates.
     *
     * @var array<string, self>
     */
    private const ALIASES = [
        'تم التسليم'        => self::Delivered,
        'في انتظار العميل'  => self::NoAnswer,
        'مرتجع'             => self::RefusedNoPayment,
    ];

    public function toStatusId(): int
    {
        return match ($this) {
            self::Delivered             => 5,
            self::DeliveredPriceChanged => 6,
            self::PartialDelivery       => 7,
            self::RefusedPaidShipping   => 8,
            self::RefusedNoPayment      => 9,
            self::CustomerCancelled     => 10,
            self::NoAnswer              => 11,
            self::PhoneOff              => 12,
            self::UnsafeArea            => 14,
            self::OutsideGovernorate    => 16,
            self::WrongPhone            => 17,
            self::Postponed             => 15,
            self::OutForDelivery        => 3,
            self::Assigned              => 2,
        };
    }

    /**
     * هل هذه الحالة لها تحصيل مالي؟
     * يطابق requiresCollection() في CLAUDE.md §4.
     */
    public function hasCollection(): bool
    {
        return in_array($this, [
            self::Delivered,
            self::DeliveredPriceChanged,
            self::PartialDelivery,
            self::RefusedPaidShipping,
        ], true);
    }

    /**
     * هل هذه الحالة نهائية (terminal)؟
     */
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

    /**
     * هل هذه الحالة تتطلب تاريخ تأجيل؟
     */
    public function requiresPostponedDate(): bool
    {
        return $this === self::Postponed;
    }

    /**
     * يقبل النص العربي الخام من الشيت ويعيد الـ case المناسب.
     * يُعيد null إذا لم يُعرف النص — Use Case يستخدم pending (1) كـ fallback.
     */
    public static function fromArabic(string $value): ?self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        foreach (self::cases() as $case) {
            if ($trimmed === $case->value) {
                return $case;
            }
        }

        return self::ALIASES[$trimmed] ?? null;
    }
}
