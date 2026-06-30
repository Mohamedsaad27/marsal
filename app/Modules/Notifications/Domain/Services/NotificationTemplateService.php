<?php

namespace App\Modules\Notifications\Domain\Services;

use App\Modules\Notifications\Domain\DTOs\NotificationMessageDTO;
use App\Modules\Notifications\Domain\Enums\NotificationTypeEnum;

/**
 * NotificationTemplateService
 *
 * The SINGLE source of truth for all notification messages in the Mersal system.
 * All Arabic titles and bodies are defined here — nowhere else.
 *
 * Usage:
 *   $message = $service->build(NotificationTypeEnum::NewOrder, ['order_code' => 'MRS-0042']);
 *   // $message->titleAr => "📦 طلب توصيل جديد"
 *   // $message->bodyAr  => "تم تعيين الطلب رقم MRS-0042 إليك — يرجى المراجعة والتحرك فوراً"
 *
 * Variable interpolation: use {{variable_name}} in the template strings.
 * Any unknown {{key}} is left as-is (no silent failure).
 *
 * Pure domain service — no Laravel facades, no DB, fully unit-testable.
 */
class NotificationTemplateService
{
    /**
     * Build a ready-to-use Arabic notification message for the given type.
     *
     * @param  NotificationTypeEnum  $type  The notification category
     * @param  array<string, string> $vars  Runtime values to substitute into the template
     */
    public function build(NotificationTypeEnum $type, array $vars = []): NotificationMessageDTO
    {
        [$titleTemplate, $bodyTemplate] = $this->templates($type);

        return new NotificationMessageDTO(
            titleAr: $this->interpolate($titleTemplate, $vars),
            bodyAr:  $this->interpolate($bodyTemplate, $vars),
        );
    }

    /**
     * Admin-facing Arabic messages for super_admin recipients.
     *
     * @param  array<string, string>  $vars
     */
    public function buildForSuperAdmin(NotificationTypeEnum $type, array $vars = []): NotificationMessageDTO
    {
        [$titleTemplate, $bodyTemplate] = $this->superAdminTemplates($type);

        return new NotificationMessageDTO(
            titleAr: $this->interpolate($titleTemplate, $vars),
            bodyAr:  $this->interpolate($bodyTemplate, $vars),
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Super-admin template definitions
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @return array{0: string, 1: string}
     */
    private function superAdminTemplates(NotificationTypeEnum $type): array
    {
        return match ($type) {
            NotificationTypeEnum::StatusChange => [
                '🔄 تحديث حالة طلب',
                'المندوب {{agent_name}} غيّر حالة الطلب {{order_code}} إلى: {{status_label}}',
            ],
            NotificationTypeEnum::ApprovalRequest => [
                '⚠️ طلب موافقة جديد',
                'المندوب {{agent_name}} يطلب موافقة على الطلب {{order_code}} — المبلغ المطلوب {{new_amount}} جنيه',
            ],
            NotificationTypeEnum::TimerStart => [
                '⏱️ بدأ مؤقت رفض الاستلام',
                'المندوب {{agent_name}} بدأ مؤقت رفض استلام الطلب {{order_code}} — المدة {{minutes}} دقيقة',
            ],
            NotificationTypeEnum::TimerExpired => [
                '⌛ انتهى مؤقت رفض الاستلام',
                'انتهى مؤقت رفض الطلب {{order_code}} دون استجابة — تم تسجيله كمرفوض بدون دفع',
            ],
            NotificationTypeEnum::Collected => [
                '💰 تحصيل نقدي من مندوب',
                'قام المندوب {{agent_name}} بتحصيل {{collected_amount}} جنيه للطلب {{order_code}}',
            ],
            NotificationTypeEnum::Settled => [
                '🧾 تسوية مالية',
                '{{settlement_action}} — {{entity_label}} بقيمة {{net_amount}} جنيه',
            ],
            NotificationTypeEnum::Returned => [
                '📦 مرتجع',
                '{{return_action}} — الطلب {{order_code}} / المندوب {{agent_name}}',
            ],
            NotificationTypeEnum::OrderReassigned => [
                '🔁 إعادة تعيين طلب',
                'تم إعادة تعيين الطلب {{order_code}} من المندوب {{old_agent}} إلى المندوب {{new_agent}}',
            ],
            default => $this->templates($type),
        };
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Template definitions — ALL human-readable Arabic messages live here
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Returns [titleTemplate, bodyTemplate] for each notification type.
     *
     * Variables available per type:
     *
     * NewOrder (1):
     *   {{order_code}}
     *
     * StatusChange (2):
     *   {{order_code}}, {{status_label}}
     *
     * ApprovalRequest (3):
     *   {{agent_name}}, {{order_code}}, {{new_amount}}
     *
     * TimerStart (4):
     *   {{agent_name}}, {{order_code}}, {{minutes}}
     *
     * TimerExpired (5):
     *   {{order_code}}
     *
     * NewMessage (6):
     *   {{sender_name}}, {{order_code}}
     *
     * PhoneUpdated (7):
     *   (none — fully static message)
     *
     * PostponedReminder (8):
     *   {{order_code}}, {{date}}
     *
     * @return array{0: string, 1: string}
     */
    private function templates(NotificationTypeEnum $type): array
    {
        return match ($type) {

            // ── 1. طلب توصيل جديد ──────────────────────────────────────────
            NotificationTypeEnum::NewOrder => [
                '📦 طلب توصيل جديد',
                'تم تعيين الطلب رقم {{order_code}} إليك — يرجى المراجعة والتحرك فوراً',
            ],

            // ── 2. تحديث حالة الطلب ────────────────────────────────────────
            NotificationTypeEnum::StatusChange => [
                '🔄 تحديث حالة الطلب',
                'الطلب رقم {{order_code}} أصبح الآن في حالة: {{status_label}}',
            ],

            // ── 3. طلب موافقة على تغيير السعر ─────────────────────────────
            NotificationTypeEnum::ApprovalRequest => [
                '⚠️ طلب موافقة على تغيير السعر',
                'المندوب {{agent_name}} يطلب تغيير سعر الطلب {{order_code}} إلى {{new_amount}} جنيه — يرجى المراجعة والرد',
            ],

            // ── 4. بدأ توقيت رفض الاستلام ─────────────────────────────────
            NotificationTypeEnum::TimerStart => [
                '⏱️ بدأ توقيت رفض الاستلام',
                'بدأ المندوب {{agent_name}} توقيت رفض استلام الطلب {{order_code}} — لديك {{minutes}} دقائق للرد قبل إغلاق الطلب تلقائياً',
            ],

            // ── 5. انتهى وقت رفض الاستلام ─────────────────────────────────
            NotificationTypeEnum::TimerExpired => [
                '⌛ انتهى وقت رفض الاستلام',
                'انتهى وقت رفض الطلب {{order_code}} دون استجابة — تم تسجيل الطلب تلقائياً كمرفوض بدون دفع',
            ],

            // ── 6. رسالة جديدة ─────────────────────────────────────────────
            NotificationTypeEnum::NewMessage => [
                '💬 رسالة جديدة',
                'أرسل {{sender_name}} رسالة جديدة في محادثة الطلب {{order_code}} — اضغط للاطلاع',
            ],

            // ── 7. تم تحديث رقم الهاتف ────────────────────────────────────
            NotificationTypeEnum::PhoneUpdated => [
                '📱 تم تحديث رقم الهاتف',
                'تم تغيير رقم هاتفك في منصة مرسال بنجاح — إذا لم تطلب ذلك يرجى التواصل مع الدعم الفني فوراً',
            ],

            // ── 8. تذكير بموعد تأجيل التسليم ─────────────────────────────
            NotificationTypeEnum::PostponedReminder => [
                '📅 تذكير بموعد تأجيل التسليم',
                'تذكير: لديك طلب {{order_code}} تم تأجيل تسليمه إلى اليوم {{date}} — يرجى التحضير والتوجه فوراً',
            ],
        };
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Variable interpolation
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Replace all {{key}} placeholders with their corresponding values.
     * Unrecognised placeholders are left unchanged.
     *
     * @param  array<string, string> $vars
     */
    private function interpolate(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{{' . $key . '}}', (string) $value, $template);
        }

        return $template;
    }
}
