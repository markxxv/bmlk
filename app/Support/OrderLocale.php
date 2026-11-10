<?php

namespace App\Support;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Runs code (emails, scheduled jobs) in the language the order was placed in,
 * so translated strings and generated URLs match what the customer saw.
 */
class OrderLocale
{
    public static function for(Order $order): string
    {
        $locale = (string) $order->locale;

        return in_array($locale, config('app.supported_locales', []), true)
            ? $locale
            : config('app.locale');
    }

    public static function run(Order $order, callable $callback): mixed
    {
        $locale = self::for($order);
        $previous = app()->getLocale();
        $previousDefault = URL::getDefaultParameters()['locale'] ?? config('app.locale');

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        URL::defaults(['locale' => $locale]);

        try {
            return $callback();
        } finally {
            app()->setLocale($previous);
            Carbon::setLocale($previous);
            URL::defaults(['locale' => $previousDefault]);
        }
    }
}
