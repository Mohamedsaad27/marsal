<?php

namespace App\Modules\Chat\Domain\Enums;

enum ConversationTypeEnum: int
{
    case AgentCompany = 1;

    public function labelAr(): string
    {
        return match ($this) {
            self::AgentCompany => 'مندوب — شركة شحن',
        };
    }
}
