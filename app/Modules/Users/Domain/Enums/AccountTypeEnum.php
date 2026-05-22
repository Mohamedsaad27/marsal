<?php

namespace App\Modules\Users\Domain\Enums;

enum AccountTypeEnum: int
{
    case SuperAdmin = 1;
    case ShippingCompany = 2;
    case DeliveryAgent = 3;
    case StaffMember = 4;

    public function code(): string
    {
        return match ($this) {
            self::SuperAdmin => 'super_admin',
            self::ShippingCompany => 'shipping_company',
            self::DeliveryAgent => 'delivery_agent',
            self::StaffMember => 'staff_member',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::SuperAdmin => 'مدير نظام',
            self::ShippingCompany => 'شركة شحن',
            self::DeliveryAgent => 'مندوب توصيل',
            self::StaffMember => 'موظف إداري',
        };
    }

    public static function fromCode(string $code): self
    {
        return match ($code) {
            'super_admin' => self::SuperAdmin,
            'shipping_company' => self::ShippingCompany,
            'delivery_agent' => self::DeliveryAgent,
            'staff_member' => self::StaffMember,
            default => throw new \InvalidArgumentException("Unknown account type: {$code}"),
        };
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_map(fn (self $type) => $type->code(), self::cases());
    }

    public function requiresShippingCompanyProfile(): bool
    {
        return $this === self::ShippingCompany;
    }

    public function requiresDeliveryAgentProfile(): bool
    {
        return $this === self::DeliveryAgent;
    }

    public function requiresStaffMemberProfile(): bool
    {
        return $this === self::StaffMember;
    }
}
