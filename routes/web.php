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
