<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * The default locale lives at unprefixed URLs, other locales under /{locale}.
 * Routes are registered twice (see routes/web.php): the prefixed copy carries a
 * {locale} parameter, the unprefixed copy is the default-locale alias.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $default = config('app.locale');
        $route = $request->route();
        $locale = $route?->parameter('locale');

        if ($locale === null) {
            $locale = $default;
        } else {
            if (! in_array($locale, config('app.supported_locales', [$default]), true)) {
                abort(404);
            }

            // /fr/... duplicates the unprefixed URL: send crawlers and users there.
            if ($locale === $default && $request->isMethodSafe()) {
                $path = '/'.ltrim(substr($request->getPathInfo(), strlen($locale) + 1), '/');
                $query = $request->getQueryString();

                return redirect($path.($query ? '?'.$query : ''), 301);
            }

            // Keep {locale} away from controller arguments.
            $route->forgetParameter('locale');
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }
}
