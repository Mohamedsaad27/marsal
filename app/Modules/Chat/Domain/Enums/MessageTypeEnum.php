<?php

namespace App\Modules\Chat\Domain\Enums;

enum MessageTypeEnum: int
{
    case Text = 1;
    case Image = 2;
    case Voice = 3;

    public function labelAr(): string
    {
        return match ($this) {
            self::Text => 'نص',
            self::Image => 'صورة',
            self::Voice => 'رسالة صوتية',
        };
    }

    public function requiresAttachment(): bool
    {
        return in_array($this, [self::Image, self::Voice], true);
    }

    public function mediaCollection(): string
    {
        return match ($this) {
            self::Image => 'image',
            self::Voice => 'voice',
            self::Text => 'default',
        };
    }
}
