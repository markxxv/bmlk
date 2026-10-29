<?php

use App\Http\Controllers\AccountController;

use App\Http\Controllers\FrontController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StripeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontController::class, 'home'])->name('home');

Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');

Route::get('/shop/category/{slug}', [ShopController::class, 'category'])
    ->where('slug', '[A-Za-z0-9-]+')
    ->name('shop.category');

Route::get('/shop/product/{slug}', [ShopController::class, 'product'])
    ->where('slug', '[A-Za-z0-9-]+')
    ->name('shop.product');

Route::get('/checkout', [ShopController::class, 'checkout'])->name('checkout');
Route::post('/checkout', [ShopController::class, 'processOrder'])->name('checkout.store');

Route::get('/api/promo/{code}', [ShopController::class, 'promo'])
    ->middleware('throttle:15,1')
    ->where('code', '[A-Za-z0-9_-]{4,32}')
    ->name('api.promo');

Route::get('/api/delivery-cost/{code}', [ShopController::class, 'deliveryCost'])
    ->where('code', '[A-Za-z0-9-]+')
    ->name('api.delivery.cost');

// TEMPORARY SENDCLOUD DIAGNOSTICS. Delete this entire group after verification.
// Both endpoints are read-only, require an authenticated shop admin, and never reveal API keys.
Route::middleware(['auth', 'throttle:6,1'])->group(function (): void {
    Route::get('/admin/sendcloud-check/addresses', function (\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse {
        abort_unless($request->user()?->is_admin === true, 403);

        $public = (string) config('services.sendcloud.public_key');
        $secret = (string) config('services.sendcloud.secret_key');

        if ($public === '' || $secret === '') {
            return response()->json(['error' => 'SENDCLOUD_PUBLIC_KEY / SENDCLOUD_SECRET_KEY are not configured.'], 503);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::acceptJson()
                ->withBasicAuth($public, $secret)
                ->connectTimeout(5)
                ->timeout(15)
                ->get('https://panel.sendcloud.sc/api/v3/addresses/sender-addresses');

            if (! $response->successful()) {
                return response()->json([
                    'success' => false,
                    'sendcloud_http_status' => $response->status(),
                    'errors' => $response->json('errors') ?? $response->json('message'),
                ], 502)->header('Cache-Control', 'no-store');
            }

            return response()->json([
                'success' => true,
                'sender_addresses' => collect($response->json('data', []))
                    ->map(fn (array $address): array => \Illuminate\Support\Arr::only($address, [
                        'id', 'company_name', 'name', 'address_line_1', 'house_number',
                        'postal_code', 'city', 'country_code',
                    ]))->values()->all(),
            ])->header('Cache-Control', 'no-store');
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('Sendcloud address diagnostics failed.', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['success' => false, 'error' => 'Sendcloud request failed.'], 502);
        }
    });

    Route::get('/admin/sendcloud-check/shipping', function (\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse {
        abort_unless($request->user()?->is_admin === true, 403);

        $public = (string) config('services.sendcloud.public_key');
        $secret = (string) config('services.sendcloud.secret_key');

        if ($public === '' || $secret === '') {
            return response()->json(['error' => 'SENDCLOUD_PUBLIC_KEY / SENDCLOUD_SECRET_KEY are not configured.'], 503);
        }

        // Run with ?country=FR, ?country=DE, ?country=ES or ?country=US.
        $destinations = [
            'FR' => ['country_code' => 'FR', 'postal_code' => '75011', 'city' => 'Paris'],
            'DE' => ['country_code' => 'DE', 'postal_code' => '10115', 'city' => 'Berlin'],
            'ES' => ['country_code' => 'ES', 'postal_code' => '28001', 'city' => 'Madrid'],
            'US' => ['country_code' => 'US', 'postal_code' => '10001', 'city' => 'New York'],
        ];

        $country = strtoupper((string) $request->query('country', 'FR'));

        if (! isset($destinations[$country])) {
            return response()->json(['error' => 'Country must be FR, DE, ES or US.'], 422);
        }

        try {
            $http = \Illuminate\Support\Facades\Http::acceptJson()
                ->withBasicAuth($public, $secret)
                ->connectTimeout(5)
                ->timeout(15);

            $addresses = $http->get('https://panel.sendcloud.sc/api/v3/addresses/sender-addresses');

            if (! $addresses->successful()) {
                return response()->json([
                    'success' => false,
                    'step' => 'sender_addresses',
                    'sendcloud_http_status' => $addresses->status(),
                    'errors' => $addresses->json('errors') ?? $addresses->json('message'),
                ], 502)->header('Cache-Control', 'no-store');
            }

            // Use an actual FR sender address configured in Sendcloud, never the legal address by assumption.
            $sender = collect($addresses->json('data', []))
                ->first(fn (array $address): bool => strtoupper((string) ($address['country_code'] ?? '')) === 'FR'
                    && str_replace(' ', '', (string) ($address['postal_code'] ?? '')) === '77400');

            $sender ??= collect($addresses->json('data', []))
                ->first(fn (array $address): bool => strtoupper((string) ($address['country_code'] ?? '')) === 'FR');

            if (! $sender) {
                return response()->json([
                    'success' => false,
                    'error' => 'No sender address in France is configured in Sendcloud. Check /admin/sendcloud-check/addresses.',
                ], 422);
            }

            $from = array_filter(\Illuminate\Support\Arr::only($sender, [
                'country_code', 'postal_code', 'city', 'address_line_1', 'house_number',
            ]), fn (mixed $value): bool => $value !== null && $value !== '');

            $contracts = $http->get('https://panel.sendcloud.sc/api/v3/contracts');

            $payload = [
                'from_address' => $from,
                'to_address' => $destinations[$country],
                // DIAGNOSTIC ONLY: illustrative test package (NOT a measured Black Milk box).
                'parcels' => [[
                    'dimensions' => [
                        'length' => '24',
                        'width' => '18',
                        'height' => '14',
                        'unit' => 'cm',
                    ],
                    'weight' => ['value' => '0.5', 'unit' => 'kg'],
                ]],
                'calculate_quotes' => true,
            ];

            $shipping = $http->post('https://panel.sendcloud.sc/api/v3/shipping-options', $payload);

            return response()->json([
                'success' => $shipping->successful(),
                'origin' => \Illuminate\Support\Arr::only($sender, [
                    'id', 'company_name', 'address_line_1', 'house_number',
                    'postal_code', 'city', 'country_code',
                ]),
                'destination' => $destinations[$country],
                'test_parcel' => $payload['parcels'][0],
                'contracts' => [
                    'http_status' => $contracts->status(),
                    'data' => $contracts->successful()
                        ? collect($contracts->json('data', []))
                            ->map(fn (array $contract): array => \Illuminate\Support\Arr::only($contract, [
                                'id', 'carrier', 'country', 'state', 'type', 'is_default_per_carrier',
                            ]))->values()->all()
                        : [],
                    'errors' => $contracts->successful() ? null : ($contracts->json('errors') ?? $contracts->json('message')),
                ],
                'shipping_options' => [
                    'http_status' => $shipping->status(),
                    'message' => $shipping->json('message'),
                    'data' => $shipping->json('data', []),
                    'errors' => $shipping->successful() ? null : $shipping->json('errors'),
                ],
            ], $shipping->successful() ? 200 : 502)->header('Cache-Control', 'no-store');
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('Sendcloud shipping diagnostics failed.', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['success' => false, 'error' => 'Sendcloud request failed.'], 502);
        }
    });
});

// Temporary Sendcloud endpoint: acknowledge connectivity tests and incoming events.
// TODO: Verify Sendcloud-Signature and process parcel events before going live.
Route::match(['get', 'post'], '/api/webhooks/sendcloud', fn () => response()->json(['status' => 'ok']))
    ->name('sendcloud.webhook');

Route::post('/stripe/webhook', [StripeController::class, 'webhook'])->name('stripe.webhook');

Route::get('/order/recover', [ShopController::class, 'recoveryForm'])
    ->name('order.recover');

Route::post('/order/recover', [ShopController::class, 'recoverOrder'])
    ->middleware('throttle:5,1')
    ->name('order.recover.submit');

Route::post('/order/{orderNumber}/pay', [StripeController::class, 'retry'])
    ->where('orderNumber', 'BM-[0-9]{8}-[0-9]{6}')
    ->name('order.pay');

Route::get('/order/{orderNumber}/thanks', [StripeController::class, 'thanks'])
    ->where('orderNumber', 'BM-[0-9]{8}-[0-9]{6}')
    ->name('order.thanks');

Route::get('/order/{orderNumber}', [ShopController::class, 'orderStatus'])
    ->where('orderNumber', 'BM-[0-9]{8}-[0-9]{6}')
    ->name('order.status');

Route::post('/account/complete-checkout', [AccountController::class, 'completeCheckout'])
    ->middleware('throttle:6,1')->name('account.complete_checkout');
Route::get('/account/checkout/{orderNumber}', [AccountController::class, 'checkoutOrder'])
    ->where('orderNumber', 'BM-[0-9]{8}-[0-9]{6}')
    ->name('account.checkout');

// Verification works even if the email link opens on a different device.
Route::get('/account/verify-email/{id}/{hash}', [AccountController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::middleware('guest')->group(function (): void {
    Route::get('/account/login', [AccountController::class, 'loginForm'])->name('account.login');
    Route::post('/account/login', [AccountController::class, 'login'])->middleware('throttle:5,1')->name('account.login.submit');
    Route::get('/account/forgot-password', [AccountController::class, 'forgotForm'])->name('password.request');
    Route::post('/account/forgot-password', [AccountController::class, 'sendResetLink'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/account/reset-password/{token}', [AccountController::class, 'resetForm'])->name('password.reset');
    Route::post('/account/reset-password', [AccountController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/account/logout', [AccountController::class, 'logout'])->name('account.logout');
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::get('/account/orders/{order}', [AccountController::class, 'order'])->name('account.order');
    Route::get('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::post('/account/password', [AccountController::class, 'savePassword'])->middleware('throttle:5,1')->name('account.password');
    Route::post('/account/verification-notification', [AccountController::class, 'resendVerification'])
        ->middleware('throttle:2,1')->name('verification.send');
    Route::get('/account/roles', [AccountController::class, 'roles'])->name('account.roles');
    Route::post('/account/roles', [AccountController::class, 'apply'])->middleware('throttle:3,1')->name('account.roles.apply');
    Route::get('/account/wholesale', [AccountController::class, 'wholesale'])->name('account.wholesale');
    Route::get('/account/influencer', [AccountController::class, 'influencer'])->name('account.influencer');
    Route::post('/account/influencer/code', [AccountController::class, 'saveCode'])
        ->middleware('throttle:3,1')->name('account.influencer.code');
});

Route::get('/events', [FrontController::class, 'events'])->name('events');

Route::get('/ou-acheter', [FrontController::class, 'whereToBuy'])->name('where-to-buy');

Route::get('/sitemap.xml', [FrontController::class, 'sitemap'])->name('sitemap');

Route::get('/{slug}', [FrontController::class, 'page'])
    ->where('slug', '[A-Za-z0-9-]+')
    ->name('page');
