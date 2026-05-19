<?php

namespace App\Modules\Users\Domain\Enums;

enum AccountTypeEnum: int
{
    case SuperAdmin = 1;
    case ShippingCompany = 2;
    case DeliveryAgent = 3;

    public function code(): string
    {
        return match ($this) {
            self::SuperAdmin => 'super_admin',
            self::ShippingCompany => 'shipping_company',
            self::DeliveryAgent => 'delivery_agent',
        };
    }

    public static function fromCode(string $code): self
    {
        return match ($code) {
            'super_admin' => self::SuperAdmin,
            'shipping_company' => self::ShippingCompany,
            'delivery_agent' => self::DeliveryAgent,
            default => throw new \InvalidArgumentException("Unknown account type: {$code}"),
        };
    }

    public function requiresShippingCompanyProfile(): bool
    {
        return $this === self::ShippingCompany;
    }

    public function requiresDeliveryAgentProfile(): bool
    {
        return $this === self::DeliveryAgent;
    }
}
