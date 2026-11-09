<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ReadOnlyCustomerPreview
{
    public function handle(Request $request, Closure $next): Response
    {
        $preview = $request->session()->get('customer_preview');

        if (! is_array($preview)) {
            return $next($request);
        }

        // Never allow a stale preview marker to grant access to another account.
        if ((int) ($preview['user_id'] ?? 0) !== (int) Auth::guard('web')->id()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            return redirect()->route('account.login');
        }

        // The only permitted POST restores the original administrator.
        if ($request->routeIs('customer-preview.stop') && $request->isMethod('POST')) {
            return $next($request);
        }

        // Explicit safe-list also blocks unusual state-changing GET endpoints
        // such as email verification, onboarding and payment completion.
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            abort(403, 'Customer preview is read-only. Return to admin to make changes.');
        }

        if (! $request->routeIs(
            'home', 'shop.index', 'shop.category', 'shop.product',
            'events', 'formations', 'where-to-buy', 'createurs', 'reparation',
            'page', 'account.index', 'account.order', 'account.profile',
            'account.roles', 'account.wholesale', 'account.influencer',
        )) {
            return redirect()->route('account.index');
        }

        return $next($request);
    }
}
