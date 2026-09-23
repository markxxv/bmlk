<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Event;
use App\Models\Page;
use App\Models\Product;
use App\Observers\TranslatableSlugObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::component('layouts.app', 'layout');

        View::composer('layouts.footer', function ($view) {
            $view->with('footerCategories', Category::query()
                ->whereKeyNot(7)
                ->whereHas('products', fn ($query) => $query->where('active', true))
                ->orderBy('id')
                ->limit(8)
                ->get(['id', 'name', 'slug']));
        });

        Product::observe(TranslatableSlugObserver::class);
        Category::observe(TranslatableSlugObserver::class);
        Page::observe(TranslatableSlugObserver::class);
        Event::observe(TranslatableSlugObserver::class);
    }
}
