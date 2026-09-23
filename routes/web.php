<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontController::class, 'home'])->name('home');

Route::get('/shop', [FrontController::class, 'shop'])->name('shop.index');

Route::get('/shop/category/{slug}', [FrontController::class, 'shopCategory'])
    ->where('slug', '[A-Za-z0-9-]+')
    ->name('shop.category');

Route::get('/shop/product/{slug}', [FrontController::class, 'shopProduct'])
    ->where('slug', '[A-Za-z0-9-]+')
    ->name('shop.product');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

Route::get('/api/delivery-cost/{code}', [CheckoutController::class, 'deliveryCost'])
    ->where('code', '[A-Za-z0-9-]+')
    ->name('api.delivery.cost');

Route::get('/order/{orderNumber}', [CheckoutController::class, 'status'])
    ->where('orderNumber', 'BM-[0-9]{8}-[0-9]{6}')
    ->name('order.status');

Route::get('/events', [FrontController::class, 'events'])->name('events');

Route::get('/ou-acheter', [FrontController::class, 'whereToBuy'])->name('where-to-buy');

Route::get('/sitemap.xml', [FrontController::class, 'sitemap'])->name('sitemap');

Route::get('/{slug}', [FrontController::class, 'page'])
    ->where('slug', '[A-Za-z0-9-]+')
    ->name('page');
