<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWarehouseStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_admin || $request->user()?->is_warehouse, 403);

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
