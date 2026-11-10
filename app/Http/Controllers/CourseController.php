<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CoursePurchase;
use App\Services\CoursePurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class CourseController extends Controller
{
    public function __construct(private CoursePurchaseService $purchases) {}

    /** One-click purchase: straight to Stripe Checkout, guests included. */
    public function buy(Request $request, string $slug): RedirectResponse
    {
        $course = Course::query()->active()->where('slug', $slug)->firstOrFail();
        abort_unless($course->isPurchasable(), 404);

        $user = $request->user();

        if ($user && $user->ownsCourse($course)) {
            return redirect()->route('account.courses')->with('success', __('Vous possédez déjà cette formation.'));
        }

        $url = $this->purchases->checkoutUrl($course, $user, $request);

        return $url
            ? redirect()->away($url)
            : redirect()->to(route('formations').'#'.$course->mode.'-'.$course->slug)
                ->with('error', __('Stripe ne répond pas pour le moment. Veuillez réessayer.'));
    }

    public function thanks(Request $request, CoursePurchase $purchase): View|RedirectResponse
    {
        abort_unless($purchase->access_token
            && hash_equals($purchase->access_token, (string) $request->query('token', '')), 404);

        // A browser redirect may precede Stripe's webhook.
        if ($purchase->status !== 'paid' && $purchase->stripe_session_id
            && $request->query('session_id') === $purchase->stripe_session_id) {
            try {
                if ($session = $this->purchases->retrieve($purchase->stripe_session_id)) {
                    $this->purchases->sync($session, 'checkout.session.completed');
                    $purchase->refresh();
                }
            } catch (Throwable $e) {
                Log::warning('Stripe course return verification failed.', ['purchase_id' => $purchase->id, 'error' => $e->getMessage()]);
            }
        }

        if ($purchase->status !== 'paid') {
            return redirect()->route('formations')->with('error', __('Le paiement n’a pas été finalisé.'));
        }

        $purchase->loadMissing(['course', 'user']);
        $user = $purchase->user;

        // Only the browser which started THIS checkout may be signed in, and only
        // into a brand-new passwordless account created by this very purchase.
        $proof = (string) $request->session()->pull("bmlk_course_login.{$purchase->id}", '');

        if ($user && ! Auth::check() && $purchase->account_created && $proof !== ''
            && $purchase->onboarding_token_hash && hash_equals($purchase->onboarding_token_hash, hash('sha256', $proof))
            && blank($user->password) && ! $user->is_admin) {
            Auth::login($user);
            $request->session()->regenerate();
            $purchase->forceFill(['onboarding_token_hash' => null])->save();
        }

        if (Auth::check() && $user && Auth::id() === $user->id) {
            return redirect()->route('account.courses')->with('success', __('Paiement confirmé : votre formation est disponible ci-dessous.'));
        }

        return view('courses.thanks', [
            'purchase' => $purchase,
            'metaTitle' => __('Merci pour votre achat | BLACK MILK'),
        ]);
    }
}
