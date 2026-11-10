<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CreatorsController;
use App\Http\Controllers\CustomerPreviewController;

use App\Http\Controllers\FrontController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\ProductRatingController;
use App\Http\Controllers\ReviewLinkController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseRefundController;
use App\Http\Middleware\EnsureWarehouseStaff;
use Illuminate\Support\Facades\Route;


// Customer preview is started by the Filament Customers action. This POST
// endpoint also makes the authorization rules directly testable.
Route::post('/_admin/customer-preview/{user}', [CustomerPreviewController::class, 'start'])
    ->middleware(['auth', 'throttle:6,1'])->name('customer-preview.start');
Route::post('/_customer-preview/stop', [CustomerPreviewController::class, 'stop'])
    ->middleware(['auth', 'throttle:12,1'])->name('customer-preview.stop');

// Warehouse is a separate, installable PWA with its own two-week session.
// Only admins and users explicitly granted is_warehouse may access orders.
Route::prefix('warehouse')->name('warehouse.')->group(function (): void {
    // Serve the service worker from the Laravel route, without creating a
    // physical public/warehouse directory that would shadow this whole prefix.
    Route::get('/sw.js', static fn () => response()->file(public_path('pwa-warehouse/sw.js'), [
        'Content-Type' => 'application/javascript; charset=UTF-8',
        'Cache-Control' => 'no-store',
    ]));

    Route::get('/login', [WarehouseController::class, 'loginForm'])->name('login');
    Route::post('/login', [WarehouseController::class, 'login'])
        ->middleware('throttle:5,1')->name('login.submit');

    Route::middleware(['auth', EnsureWarehouseStaff::class])->group(function (): void {
        Route::post('/logout', [WarehouseController::class, 'logout'])->name('logout');
        Route::get('/', [WarehouseController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order:order_number}', [WarehouseController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order:order_number}/status', [WarehouseController::class, 'status'])->name('orders.status');
        Route::post('/orders/{order:order_number}/refund', [WarehouseRefundController::class, 'store'])
            ->middleware('throttle:3,1')->name('orders.refund');
        Route::post('/orders/{order:order_number}/refunds/{refund}/refresh', [WarehouseRefundController::class, 'refresh'])
            ->middleware('throttle:6,1')->name('orders.refunds.refresh');
        Route::post('/orders/{order:order_number}/label', [WarehouseController::class, 'createLabel'])
            ->middleware('throttle:5,1')->name('orders.create-label');
        Route::get('/orders/{order:order_number}/label', [WarehouseController::class, 'label'])->name('orders.label');
    });
});

// --- Not localized: signed/emailed links, JSON APIs and webhooks stay unprefixed. ---

// Email-only review links grant rating access, not account authentication.
Route::get('/avis/{token}', [ReviewLinkController::class, 'show'])
    ->where('token', '[a-f0-9]{64}')
    ->middleware('throttle:30,1')->name('reviews.show');
Route::post('/avis/{token}', [ReviewLinkController::class, 'store'])
    ->where('token', '[a-f0-9]{64}')
    ->middleware('throttle:10,1')->name('reviews.store');

// A scanner may prefetch GET links: opt-out is confirmed by a CSRF-protected POST.
Route::get('/avis/desabonnement/{order}', [ReviewLinkController::class, 'unsubscribeForm'])
    ->middleware(['signed', 'throttle:15,1'])->name('reviews.unsubscribe');
Route::post('/avis/desabonnement/{order}', [ReviewLinkController::class, 'unsubscribe'])
    ->middleware(['signed', 'throttle:5,1'])->name('reviews.unsubscribe.store');

Route::post('/api/cart/availability', [ShopController::class, 'cartAvailability'])
    ->middleware('throttle:60,1')->name('api.cart.availability');

Route::get('/api/promo/{code}', [ShopController::class, 'promo'])
    ->middleware('throttle:15,1')
    ->where('code', '[A-Za-z0-9_-]{4,32}')
    ->name('api.promo');

// Sendcloud v3: live quotes, service points, secure tracking feedback and admin PDF.
Route::post('/api/sendcloud/quote', [\App\Http\Controllers\SendcloudController::class, 'quote'])
    ->middleware('throttle:20,1')->name('sendcloud.quote');
Route::get('/api/sendcloud/points', [\App\Http\Controllers\SendcloudController::class, 'points'])
    ->middleware('throttle:20,1')->name('sendcloud.points');
Route::post('/api/webhooks/sendcloud', [\App\Http\Controllers\SendcloudController::class, 'webhook'])
    ->name('sendcloud.webhook');
Route::get('/api/sendcloud/labels/{order}', [\App\Http\Controllers\SendcloudController::class, 'label'])
    ->middleware('auth')->name('sendcloud.label');

Route::post('/stripe/webhook', [StripeController::class, 'webhook'])->name('stripe.webhook');

// Verification works even if the email link opens on a different device.
Route::get('/account/verify-email/{id}/{hash}', [AccountController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// Emailed token link: always unprefixed, renders in the default locale.
Route::middleware('guest')->get('/account/reset-password/{token}', [AccountController::class, 'resetForm'])
    ->name('password.reset');

Route::get('/sitemap.xml', [FrontController::class, 'sitemap'])->name('sitemap');

// --- Localized storefront ---------------------------------------------------
// Registered twice with identical names: the prefixed copy (/en/..., /es/...)
// comes first, so route() resolves to it and URL::defaults() fills {locale}.
// The unprefixed copy serves the default locale (see SetLocale and the URL
// path formatter in AppServiceProvider, which strips the default prefix).
// The catch-all page route must stay last inside the group.
$localized = function (): void {
    Route::get('/', [FrontController::class, 'home'])->name('home');

    Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');

    Route::get('/shop/category/{slug}', [ShopController::class, 'category'])
        ->where('slug', '[A-Za-z0-9-]+')
        ->name('shop.category');

    Route::get('/shop/product/{slug}', [ShopController::class, 'product'])
        ->where('slug', '[A-Za-z0-9-]+')
        ->name('shop.product');

    Route::view('/livraison', 'livraison')->name('delivery.terms');

    Route::get('/checkout', [ShopController::class, 'checkout'])->name('checkout');
    Route::post('/checkout', [ShopController::class, 'processOrder'])->name('checkout.store');


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


    Route::middleware('guest')->group(function (): void {
        Route::get('/account/login', [AccountController::class, 'loginForm'])->name('account.login');
        Route::post('/account/login', [AccountController::class, 'login'])->middleware('throttle:5,1')->name('account.login.submit');
        Route::get('/account/forgot-password', [AccountController::class, 'forgotForm'])->name('password.request');
        Route::post('/account/forgot-password', [AccountController::class, 'sendResetLink'])->middleware('throttle:3,1')->name('password.email');
        Route::post('/account/reset-password', [AccountController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/account/logout', [AccountController::class, 'logout'])->name('account.logout');
        Route::get('/account', [AccountController::class, 'index'])->name('account.index');
        Route::get('/account/orders/{order}', [AccountController::class, 'order'])->name('account.order');
        Route::post('/account/orders/{order}/ratings', [ProductRatingController::class, 'store'])
            ->middleware('throttle:20,1')->name('account.ratings.store');
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

    // Public programme / lightweight application, stored in existing role_applications.
    Route::get('/createurs', [CreatorsController::class, 'index'])->name('createurs');
    Route::post('/createurs', [CreatorsController::class, 'apply'])
        ->middleware('throttle:3,1')->name('createurs.apply');

    // Workshop requests are sent directly to Telegram without a queue or checkout.
    Route::get('/reparation', [RepairController::class, 'index'])->name('reparation');
    Route::post('/reparation', [RepairController::class, 'submit'])
        ->middleware('throttle:3,1')->name('reparation.submit');

    Route::get('/events', [FrontController::class, 'events'])->name('events');
    Route::get('/formations', [FrontController::class, 'formations'])->name('formations');

    Route::get('/ou-acheter', [FrontController::class, 'whereToBuy'])->name('where-to-buy');


    Route::get('/{slug}', [FrontController::class, 'page'])
        ->where('slug', '[A-Za-z0-9-]+')
        ->name('page');
};

Route::prefix('{locale}')
    ->where(['locale' => implode('|', config('app.supported_locales', ['fr']))])
    ->group($localized);

Route::group([], $localized);
