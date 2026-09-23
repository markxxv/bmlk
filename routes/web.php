<?php

use App\Http\Controllers\FrontController;
use App\Http\Controllers\ShopController;
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

Route::get('/api/delivery-cost/{code}', [ShopController::class, 'deliveryCost'])
    ->where('code', '[A-Za-z0-9-]+')
    ->name('api.delivery.cost');

Route::get('/order/{orderNumber}', [ShopController::class, 'orderStatus'])
    ->where('orderNumber', 'BM-[0-9]{8}-[0-9]{6}')
    ->name('order.status');

Route::get('/events', [FrontController::class, 'events'])->name('events');

Route::get('/ou-acheter', [FrontController::class, 'whereToBuy'])->name('where-to-buy');

Route::get('/sitemap.xml', [FrontController::class, 'sitemap'])->name('sitemap');

Route::get('/{slug}', [FrontController::class, 'page'])
    ->where('slug', '[A-Za-z0-9-]+')
    ->name('page');
