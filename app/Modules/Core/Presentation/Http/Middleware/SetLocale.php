<?php

namespace App\Modules\Core\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $locale    = $request->header('Accept-Language');
        $supported = (array) config('core.supported_locales', ['ar', 'en']);

        if (! is_string($locale) || ! in_array($locale, $supported, true)) {
            $locale = (string) config('core.default_locale', config('app.locale', 'en'));
        }

        App::setLocale($locale);

        return $next($request);
    }
}
