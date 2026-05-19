<?php

namespace App\Modules\Core\Presentation\Http\Middleware;

use App\Modules\Core\Application\Exceptions\TenantNotResolvedException;
use App\Modules\Core\Infrastructure\Helpers\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant_id from the authenticated JWT user and binds it
 * into the application container so every downstream layer (repositories,
 * domain services, jobs dispatched in-request) can scope by tenant_id
 * without re-reading the auth guard.
 *
 * Applied as alias `scope.tenant` to all /api/v1/{module}/... routes.
 */
class ScopeTenant
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = config('core.tenant.guard', 'api');
        $user  = auth($guard)->user();

        if ($user === null) {
            throw new \Illuminate\Auth\AuthenticationException(
                __('core::messages.unauthenticated'),
                [config('core.tenant.guard', 'api')]
            );
        }

        $tenantId = $user->tenant_id ?? null;

        if (! is_string($tenantId) || $tenantId === '') {
            throw new TenantNotResolvedException();
        }

        TenantContext::set($tenantId);
        $request->attributes->set(TenantContext::KEY, $tenantId);

        return $next($request);
    }
}
