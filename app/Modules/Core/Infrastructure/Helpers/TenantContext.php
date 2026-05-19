<?php

namespace App\Modules\Core\Infrastructure\Helpers;

class TenantContext
{
    public const KEY = 'tenant_id';

    public static function set(string $tenantId): void
    {
        $key = config('core.tenant.container_key', self::KEY);
        app()->instance($key, $tenantId);
    }

    public static function get(): ?string
    {
        $key = config('core.tenant.container_key', self::KEY);

        if (! app()->bound($key)) {
            return null;
        }

        $tenantId = app($key);

        return is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
    }

    public static function clear(): void
    {
        $key = config('core.tenant.container_key', self::KEY);

        if (app()->bound($key)) {
            app()->forgetInstance($key);
        }
    }
}
