@php
    $locales = config('app.supported_locales', ['fr']);
    $default = config('app.locale');

    // One <url> per locale, each listing every language version as alternates.
    // $slugs: null for static routes, or a locale => slug map for slug routes.
    $urlsFor = function (string $name, ?array $slugs = null) use ($locales, $default): array {
        $urls = [];
        foreach ($locales as $locale) {
            $params = ['locale' => $locale];
            if ($slugs !== null) {
                $slug = $slugs[$locale] ?? $slugs['fr'] ?? null;
                if (! $slug) {
                    continue;
                }
                $params['slug'] = $slug;
            }
            $urls[$locale] = route($name, $params);
        }

        return $urls;
    };
@endphp
{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
>
    @foreach (['home', 'shop.index', 'events', 'formations', 'createurs', 'where-to-buy', 'delivery.terms'] as $routeName)
        @include('sitemap-entry', ['urls' => $urlsFor($routeName), 'default' => $default])
    @endforeach

    @foreach ($categories as $category)
        @include('sitemap-entry', [
            'urls' => $urlsFor('shop.category', $category->getTranslations('slug')),
            'default' => $default,
            'lastmod' => $category->updated_at?->toAtomString(),
        ])
    @endforeach

    @foreach ($products as $product)
        @include('sitemap-entry', [
            'urls' => $urlsFor('shop.product', $product->getTranslations('slug')),
            'default' => $default,
            'lastmod' => $product->updated_at?->toAtomString(),
            'image' => $product->getFirstMediaUrl('images'),
            'imageTitle' => $product->getTranslation('name', 'fr', false),
        ])
    @endforeach
</urlset>
