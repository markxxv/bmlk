<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * URLs of the current page in every supported locale (language switcher and
 * hreflang). Controllers of slug-based pages publish their per-locale slugs
 * with LocaleUrls::slugs() because slugs differ between languages.
 */
class LocaleUrls
{
    /** Routes that are indexable and therefore get hreflang alternates. */
    private const INDEXABLE = [
        'home', 'shop.index', 'shop.category', 'shop.product', 'delivery.terms',
        'createurs', 'reparation', 'events', 'formations', 'where-to-buy', 'page',
    ];

    public static function slugs(?array $slugs): void
    {
        request()->attributes->set('locale_slugs', array_filter((array) $slugs));
    }

    /** @return array<string, string> locale => absolute URL, empty if the page is not localized */
    public static function all(): array
    {
        $current = request()->route();
        $name = $current?->getName();

        if (! $name || ! in_array('GET', $current->methods(), true)) {
            return [];
        }

        // The first registered route with this name is the {locale}-prefixed copy.
        $named = Route::getRoutes()->getByName($name);

        if (! $named || ! str_starts_with($named->uri(), '{locale}')) {
            return [];
        }

        $params = $current->parameters();
        $slugs = request()->attributes->get('locale_slugs');
        $urls = [];

        foreach (config('app.supported_locales', []) as $locale) {
            $localeParams = $params;

            if (array_key_exists('slug', $params)) {
                if (! is_array($slugs) || $slugs === []) {
                    return [];
                }

                $localeParams['slug'] = $slugs[$locale] ?? $slugs['fr'] ?? $params['slug'];
            }

            $urls[$locale] = route($name, $localeParams + ['locale' => $locale]);
        }

        return $urls;
    }

    /** @return array<string, string> hreflang code => URL, including x-default */
    public static function hreflang(): array
    {
        if (! in_array(request()->route()?->getName(), self::INDEXABLE, true)) {
            return [];
        }

        $urls = self::all();

        if ($urls === []) {
            return [];
        }

        $default = config('app.locale');

        if (isset($urls[$default])) {
            $urls['x-default'] = $urls[$default];
        }

        return $urls;
    }
}
