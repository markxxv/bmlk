{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
>
    <url>
        <loc>{{ route('home') }}</loc>
    </url>
    <url>
        <loc>{{ route('shop.index') }}</loc>
    </url>
    <url>
        <loc>{{ route('events') }}</loc>
    </url>
    <url>
        <loc>{{ route('where-to-buy') }}</loc>
    </url>

    @foreach ($categories as $category)
        @php
            $slug = $category->getTranslation('slug', 'fr', false);
        @endphp
        @if ($slug)
            <url>
                <loc>{{ route('shop.category', ['slug' => $slug]) }}</loc>
                <lastmod>{{ $category->updated_at?->toAtomString() }}</lastmod>
            </url>
        @endif
    @endforeach

    @foreach ($products as $product)
        @php
            $slug = $product->getTranslation('slug', 'fr', false);
            $image = $product->getFirstMediaUrl('images');
            $name = $product->getTranslation('name', 'fr', false);
        @endphp
        @if ($slug)
            <url>
                <loc>{{ route('shop.product', ['slug' => $slug]) }}</loc>
                <lastmod>{{ $product->updated_at?->toAtomString() }}</lastmod>
                @if ($image)
                    <image:image>
                        <image:loc>{{ $image }}</image:loc>
                        <image:title>{{ $name }}</image:title>
                    </image:image>
                @endif
            </url>
        @endif
    @endforeach
</urlset>
