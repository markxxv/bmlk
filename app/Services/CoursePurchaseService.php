<?php

namespace App\Services;

use App\Mail\CoursePurchasedMail;
use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class CoursePurchaseService
{
    /**
     * Starts a one-click Stripe Checkout for a course and returns its URL.
     * Works for guests: Stripe collects the e-mail and the account is
     * attached after the payment is confirmed.
     */
    public function checkoutUrl(Course $course, ?User $user, Request $request): ?string
    {
        if (! config('services.stripe.secret')) {
            Log::warning('Stripe secret key is not configured.');

            return null;
        }

        $sessionProof = Str::random(64);

        $purchase = CoursePurchase::query()->create([
            'user_id' => $user?->id,
            'email' => $user?->email,
            'course_id' => $course->id,
            'status' => 'pending',
            'amount_cents' => $course->priceCents(),
            'currency' => 'eur',
            'locale' => app()->getLocale(),
            'access_token' => Str::random(64),
            'onboarding_token_hash' => hash('sha256', $sessionProof),
        ]);

        // Proof that THIS browser started the checkout (see AccountController::completeCheckout).
        $request->session()->put("bmlk_course_login.{$purchase->id}", $sessionProof);

        try {
            $params = [
                'mode' => 'payment',
                'locale' => app()->getLocale(),
                'client_reference_id' => 'course-'.$purchase->id,
                'metadata' => ['course_purchase_id' => (string) $purchase->id, 'course_id' => (string) $course->id],
                'payment_intent_data' => ['metadata' => ['course_purchase_id' => (string) $purchase->id]],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => $purchase->amount_cents,
                        'product_data' => ['name' => Str::limit($course->name, 150)],
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => $this->thanksUrl($purchase).'&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('formations').'#'.$course->mode.'-'.$course->slug,
            ];
            if ($user?->email) {
                $params['customer_email'] = $user->email;
            }

            $response = Http::asForm()
                ->withToken(config('services.stripe.secret'))
                ->withHeaders(['Idempotency-Key' => "bmlk-course-purchase-{$purchase->id}"])
                ->timeout(15)
                ->post('https://api.stripe.com/v1/checkout/sessions', $params);

            if (! $response->successful() || ! $response->json('id') || ! $response->json('url')) {
                Log::warning('Stripe course session creation failed.', [
                    'purchase_id' => $purchase->id,
                    'http_status' => $response->status(),
                    'error' => $response->json('error.message'),
                ]);
                $purchase->delete();

                return null;
            }

            $purchase->forceFill(['stripe_session_id' => $response->json('id')])->save();

            return $response->json('url');
        } catch (Throwable $e) {
            Log::warning('Stripe Checkout unavailable for course.', ['purchase_id' => $purchase->id, 'error' => $e->getMessage()]);
            $purchase->delete();

            return null;
        }
    }

    public function thanksUrl(CoursePurchase $purchase): string
    {
        return route('courses.thanks', ['purchase' => $purchase->id, 'token' => $purchase->access_token, 'locale' => $purchase->locale]);
    }

    public function retrieve(string $sessionId): ?array
    {
        $response = Http::withToken(config('services.stripe.secret'))
            ->timeout(12)
            ->get('https://api.stripe.com/v1/checkout/sessions/'.rawurlencode($sessionId));

        return $response->successful() ? $response->json() : null;
    }

    /** Applies a verified Checkout Session to its purchase. Idempotent. */
    public function sync(array $session, string $event): void
    {
        $purchase = CoursePurchase::query()->with('course')->find(data_get($session, 'metadata.course_purchase_id'));
        $sessionId = (string) ($session['id'] ?? '');

        if (! $purchase
            || ($session['mode'] ?? null) !== 'payment'
            || strtolower((string) ($session['currency'] ?? '')) !== 'eur'
            || (int) ($session['amount_total'] ?? -1) !== $purchase->amount_cents
            || ! Str::startsWith($sessionId, 'cs_')
            || $purchase->stripe_session_id !== $sessionId) {
            return;
        }

        $paid = ($session['payment_status'] ?? null) === 'paid'
            && in_array($event, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true);

        if ($purchase->status === 'paid') {
            return;
        }

        if (! $paid) {
            if (in_array($event, ['checkout.session.async_payment_failed', 'checkout.session.expired'], true)) {
                $purchase->forceFill(['status' => $event === 'checkout.session.expired' ? 'expired' : 'failed'])->save();
            }

            return;
        }

        $stripeEmail = data_get($session, 'customer_details.email');
        $email = filter_var($stripeEmail, FILTER_VALIDATE_EMAIL) ? Str::lower(trim($stripeEmail)) : $purchase->email;
        $name = (string) data_get($session, 'customer_details.name', '');

        $changed = DB::transaction(function () use ($purchase, $session, $email, $name): bool {
            $updated = CoursePurchase::query()->whereKey($purchase->id)->where('status', '!=', 'paid')->update([
                'status' => 'paid',
                'email' => $email,
                'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
                'paid_at' => now(),
                'updated_at' => now(),
            ]);

            if ($updated !== 1) {
                return false;
            }

            $purchase->refresh();
            $this->attachCustomer($purchase, $name);

            return true;
        });

        if ($changed) {
            $this->sendConfirmation($purchase->fresh(['course', 'user']));
        }
    }

    /**
     * Attach the purchase to the account owning the e-mail, creating a
     * passwordless one for new customers (same model as shop orders).
     * Existing accounts are never signed in from an anonymous purchase.
     */
    private function attachCustomer(CoursePurchase $purchase, string $name): void
    {
        if ($purchase->user_id || blank($purchase->email)) {
            return;
        }

        $email = Str::lower(trim($purchase->email));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        $created = false;

        if (! $user) {
            $user = User::query()->create([
                'email' => $email,
                'name' => $name !== '' ? $name : Str::before($email, '@'),
                'password' => null,
            ]);
            $created = true;
        }

        // A second paid purchase of the same course by the same account is impossible
        // (unique index); keep the guest row unattached in that edge case.
        if (CoursePurchase::query()->where('user_id', $user->id)->where('course_id', $purchase->course_id)->where('status', 'paid')->exists()) {
            return;
        }

        $purchase->forceFill(['user_id' => $user->id, 'account_created' => $created])->save();
    }

    private function sendConfirmation(CoursePurchase $purchase): void
    {
        if (blank($purchase->email)) {
            return;
        }

        try {
            Mail::to($purchase->email)->locale($purchase->locale)->send(new CoursePurchasedMail($purchase));
        } catch (Throwable $e) {
            Log::warning('Course purchase email failed.', ['purchase_id' => $purchase->id, 'error' => $e->getMessage()]);
        }
    }
}
