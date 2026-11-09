<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomerPreviewController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();

        abort_unless($admin?->is_admin === true, 403);
        abort_if($request->session()->has('customer_preview'), 403);
        abort_if($user->is_admin || $user->is_warehouse || $user->is($admin), 403);

        // Isolate the customer preview from all previous account/session data.
        $request->session()->invalidate();
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $request->session()->put('customer_preview', [
            'admin_id' => $admin->getKey(),
            'user_id' => $user->getKey(),
            'admin_password_digest' => hash('sha256', (string) $admin->getAuthPassword()),
            'started_at' => now()->toIso8601String(),
        ]);

        Log::notice('Admin started read-only customer preview.', [
            'admin_id' => $admin->getKey(),
            'customer_id' => $user->getKey(),
            'ip' => $request->ip(),
        ]);

        return redirect()->route('account.index');
    }

    public function stop(Request $request): RedirectResponse
    {
        $preview = $request->session()->get('customer_preview');
        abort_unless(is_array($preview), 403);
        abort_unless((int) ($preview['user_id'] ?? 0) === (int) Auth::guard('web')->id(), 403);

        $admin = User::query()->find($preview['admin_id'] ?? null);
        $canRestore = $admin?->is_admin === true
            && hash_equals(
                (string) ($preview['admin_password_digest'] ?? ''),
                hash('sha256', (string) $admin->getAuthPassword()),
            );

        Log::notice('Admin ended read-only customer preview.', [
            'admin_id' => $preview['admin_id'] ?? null,
            'customer_id' => $preview['user_id'] ?? null,
            'ip' => $request->ip(),
        ]);

        $request->session()->invalidate();

        if (! $canRestore) {
            Auth::guard('web')->logout();
            return redirect()->route('account.login');
        }

        Auth::guard('web')->login($admin);
        $request->session()->regenerate();

        return redirect()->route('filament.admin.resources.customers.index');
    }
}
