<?php

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

Route::get('/ou-acheter', [FrontController::class, 'whereToBuy'])->name('where-to-buy');
