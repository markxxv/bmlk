<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * Complete checkout onboarding with a browser-only secret returned to
     * the original first-party checkout. The order's URL token is not enough
     * to sign in. Existing accounts are never taken over from a guest order.
     */
    public function completeCheckout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'regex:/^BM-[0-9]{8}-[0-9]{6}$/'],
            'token' => ['required', 'string', 'size:64'],
        ]);

        $order = Order::query()
            ->where('order_number', $data['order_number'])
            ->where('payment_status', 'paid')
            ->first();

        if (! $order || ! $order->onboarding_token_hash || ! hash_equals(
            $order->onboarding_token_hash,
            hash('sha256', $data['token']),
        )) {
            return response()->json(['message' => __('Lien de session expiré.')], 403);
        }

        $user = $order->user;

        // Never sign in somebody who used the same email for an older
        // registered account (or an admin account). That would be a takeover.
        if ($user && (int) $user->onboarding_order_id === $order->id
            && blank($user->password) && ! $user->is_admin
            && (! Auth::check() || Auth::id() === $user->id)) {
            Auth::login($user);
            $request->session()->regenerate();

            $order->forceFill(['onboarding_token_hash' => null])->save();
            if (! $user->hasVerifiedEmail()) {
                try {
                    $user->sendEmailVerificationNotification();
                } catch (\Throwable $exception) {
                    Log::warning('Account verification email failed', [
                        'user_id' => $user->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'url' => route('account.index'),
                'authenticated' => true,
            ]);
        }

        // The original purchaser can always see this ONE paid order.
        // An existing account requires its own authenticated login.
        return response()->json([
            'url' => route('account.checkout', [
                'orderNumber' => $order->order_number,
                'token' => $order->access_token,
            ]),
            'authenticated' => false,
        ]);
    }

    public function checkoutOrder(Request $request, string $orderNumber): View
    {
        $order = Order::query()->where('order_number', $orderNumber)
            ->where('payment_status', 'paid')->firstOrFail();

        abort_unless($order->access_token
            && hash_equals($order->access_token, (string) $request->query('token', '')), 404);

        if (Auth::check() && $order->user_id === Auth::id()) {
            return view('account.order', ['order' => $order]);
        }

        return view('account.checkout-order', ['order' => $order]);
    }

    public function loginForm(): View { return view('account.login'); }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        $email = Str::lower(trim($data['email']));
        $canonical = \App\Models\User::query()->whereRaw('LOWER(email) = ?', [$email])->value('email') ?? $email;

        if (! Auth::attempt(['email' => $canonical, 'password' => $data['password']], $request->boolean('remember'))) {
            return back()->withErrors(['email' => __('Identifiants incorrects.')])->onlyInput('email');
        }

        $request->session()->regenerate();
        $this->claimOrders($request);
        return redirect()->intended(route('account.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    public function index(Request $request): View
    {
        $this->claimOrders($request);
        $user = $request->user();

        return view('account.index', [
            'user' => $user,
            // The originating paid order is visible immediately, even while
            // the new customer's email still awaits verification.
            'currentOrder' => $user->orders()
                ->whereKey($user->onboarding_order_id)
                ->first(),
            'orders' => $user->orders()->latest()->paginate(12),
        ]);
    }

    public function order(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        return view('account.order', compact('order'));
    }

    public function profile(Request $request): View
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function savePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => [$request->user()->password ? 'required' : 'nullable', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)],
        ]);
        $request->user()->forceFill(['password' => $data['password']])->save();
        $request->session()->regenerate();
        return back()->with('success', __('Mot de passe enregistré.'));
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();
        $this->claimOrders($request);
        return redirect()->route('account.index')->with('success', __('E-mail confirmé.'));
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }
        return back()->with('success', __('Lien de confirmation envoyé.'));
    }

    public function forgotForm(): View { return view('account.forgot'); }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $email = Str::lower(trim($data['email']));
        $canonical = \App\Models\User::query()->whereRaw('LOWER(email) = ?', [$email])->value('email') ?? $email;
        Password::sendResetLink(['email' => $canonical]);
        return back()->with('success', __('Si ce compte existe, un lien vous a été envoyé.'));
    }

    public function resetForm(string $token, Request $request): View
    {
        return view('account.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)],
        ]);
        $status = Password::reset($data, function ($user, $password): void {
            $user->forceFill(['password' => $password])->save();
            $user->setRememberToken(Str::random(60));
            $user->save();
            event(new PasswordReset($user));
        });
        return $status === Password::PASSWORD_RESET
            ? redirect()->route('account.login')->with('success', __('Mot de passe créé. Connectez-vous.'))
            : back()->withErrors(['email' => __($status)]);
    }

    public function roles(Request $request): View
    {
        return view('account.roles', [
            'user' => $request->user(),
            'applications' => $request->user()->roleApplications()->latest()->get()->keyBy('role'),
        ]);
    }

    public function apply(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasVerifiedEmail(), 403);
        $data = $request->validate([
            'role' => ['required', Rule::in(['wholesale', 'influencer'])],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);
        $user = $request->user();
        $field = $data['role'] === 'wholesale' ? 'is_wholesale' : 'is_influencer';
        if ($user->{$field} || $user->roleApplications()
            ->where('role', $data['role'])->where('status', 'pending')->exists()) {
            return back()->with('success', __('Demande déjà enregistrée.'));
        }
        $user->roleApplications()->create([
            'role' => $data['role'],
            'message' => $data['message'] ?? null,
            'status' => 'pending',
        ]);
        return back()->with('success', __('Demande envoyée.'));
    }

    public function wholesale(Request $request): View
    {
        abort_unless($request->user()->is_wholesale && $request->user()->hasVerifiedEmail(), 403);
        return view('account.wholesale', ['user' => $request->user()]);
    }

    public function influencer(Request $request): View
    {
        abort_unless($request->user()->is_influencer && $request->user()->hasVerifiedEmail(), 403);
        return view('account.influencer', ['user' => $request->user()]);
    }

    public function saveCode(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->is_influencer && $user->hasVerifiedEmail() && blank($user->influencer_code), 403);
        $request->merge(['code' => Str::upper(trim((string) $request->input('code', '')))]);
        $data = $request->validate([
            'code' => ['required', 'regex:/^[A-Z0-9_-]{4,24}$/', Rule::unique('users', 'influencer_code')],
        ]);
        $changed = $user->newQuery()->whereKey($user->id)->whereNull('influencer_code')
            ->update(['influencer_code' => $data['code']]);
        abort_unless($changed === 1, 403);
        return back()->with('success', __('Votre code unique est enregistré définitivement.'));
    }

    private function claimOrders(Request $request): void
    {
        $user = $request->user();
        if (! $user?->hasVerifiedEmail()) return;

        Order::query()->whereNull('user_id')
            ->whereRaw('LOWER(email) = ?', [Str::lower($user->email)])
            ->where('payment_status', 'paid')
            ->update(['user_id' => $user->id]);
    }
}
