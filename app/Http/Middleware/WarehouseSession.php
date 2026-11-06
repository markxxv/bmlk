<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WarehouseSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('warehouse', 'warehouse/*')) {
            // Set this before EncryptCookies and StartSession so the warehouse
            // has its own durable login without extending customer sessions.
            config([
                'session.cookie' => 'bmlk_warehouse_session',
                'session.path' => '/warehouse',
                'session.lifetime' => 60 * 24 * 14,
                'session.expire_on_close' => false,
            ]);
        }

        return $next($request);
    }
}
