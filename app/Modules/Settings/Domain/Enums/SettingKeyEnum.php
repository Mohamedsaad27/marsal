<?php

namespace App\Modules\Settings\Domain\Enums;

enum SettingKeyEnum: string
{
    // Platform Identity
    case PlatformName    = 'platform_name';
    case LogoUrl         = 'logo_url';

    // Organization Info
    case OrgName         = 'org_name';
    case CommercialReg   = 'commercial_reg';
    case OfficialEmail   = 'official_email';
    case ContactPhone    = 'contact_phone';
    case Address         = 'address';

    /** Keys that hold a file/URL — handled via media upload, not plain text */
    public function isFile(): bool
    {
        return match ($this) {
            self::LogoUrl => true,
            default       => false,
        };
    }

    /** @return list<self> */
    public static function identityKeys(): array
    {
        return [self::PlatformName, self::LogoUrl];
    }

    /** @return list<self> */
    public static function organizationKeys(): array
    {
        return [
            self::OrgName,
            self::CommercialReg,
            self::OfficialEmail,
            self::ContactPhone,
            self::Address,
        ];
    }
}
