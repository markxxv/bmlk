<x-layout
    :title="$metaTitle"
    :meta-description="$metaDescription"
    :canonical="$canonical"
>
    @php
        $locale = in_array(app()->getLocale(), ['fr', 'en', 'ro'], true) ? app()->getLocale() : 'fr';
        $pageTitle = $currentCategory
            ? ($currentCategory->getTranslation('name', $locale, false) ?: $currentCategory->getTranslation('name', 'fr', false))
            : __('Boutique');

        $collectionSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $pageTitle,
            'description' => $metaDescription,
            'url' => $canonical,
        ];
    @endphp

    <script type="application/ld+json">
        {!! json_encode($collectionSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    <main>
        <section class="px-3 pb-10 pt-12 sm:px-5 sm:pb-14 sm:pt-16 lg:px-8 lg:pb-16 lg:pt-20">
            <div class="mx-auto max-w-[1560px]">
                <nav aria-label="{{ __('Fil d’Ariane') }}" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-zinc-500">
                    <a href="{{ route('home') }}" class="transition hover:text-zinc-900">
                        {{ __('Accueil') }}
                    </a>

                    <x-lucide-chevron-right class="h-3 w-3" />

                    @if ($currentCategory)
                        <a href="{{ route('shop.index') }}" class="transition hover:text-zinc-900">
                            {{ __('Boutique') }}
                        </a>

                        <x-lucide-chevron-right class="h-3 w-3" />

                        <span class="text-zinc-900">{{ $pageTitle }}</span>
                    @else
                        <span class="text-zinc-900">{{ __('Boutique') }}</span>
                    @endif
                </nav>

                <div class="mt-8 grid gap-8 lg:grid-cols-12 lg:items-end">
                    <div class="lg:col-span-8">
                        <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                            {{ $currentCategory ? __('Collection BLACK MILK') : __('Catalogue professionnel') }}
                        </p>

                        <h1 class="mt-4 max-w-5xl font-['Playfair_Display'] text-5xl font-medium leading-none tracking-tight text-zinc-900 sm:text-6xl lg:text-7xl">
                            {{ $pageTitle }}
                        </h1>
                    </div>

                    <div class="lg:col-span-4 lg:text-right">
                        <p class="text-sm text-zinc-500">
                            {{ trans_choice(':count produit|:count produits', $products->total(), ['count' => $products->total()]) }}
                        </p>
                    </div>
                </div>

                <nav
                    aria-label="{{ __('Catégories de produits') }}"
                    class="-mx-3 mt-10 flex gap-2 overflow-x-auto px-3 pb-2 sm:-mx-5 sm:px-5 lg:mx-0 lg:px-0"
                >
                    <a
                        href="{{ route('shop.index') }}"
                        class="shrink-0 rounded-full px-5 py-3 text-xs font-semibold uppercase tracking-widest transition {{ $currentCategory ? 'bg-white text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' : 'bg-[#DDA1AA] text-zinc-900' }}"
                    >
                        {{ __('Tous les produits') }}
                    </a>

                    @foreach ($categories as $category)
                        @php
                            $categorySlug = $category->getTranslation('slug', $locale, false)
                                ?: $category->getTranslation('slug', 'fr', false);

                            $categoryName = $category->getTranslation('name', $locale, false)
                                ?: $category->getTranslation('name', 'fr', false);

                            $active = $currentCategory?->id === $category->id;
                        @endphp

                        <a
                            href="{{ route('shop.category', ['slug' => $categorySlug]) }}"
                            class="shrink-0 rounded-full px-5 py-3 text-xs font-semibold uppercase tracking-widest transition {{ $active ? 'bg-[#DDA1AA] text-zinc-900' : 'bg-white text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}"
                        >
                            {{ $categoryName }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </section>

        <section class="px-3 pb-16 sm:px-5 sm:pb-20 lg:px-8 lg:pb-24">
            <div class="mx-auto max-w-[1560px]">
                @if ($products->count())
                    <div class="grid grid-cols-2 gap-x-3 gap-y-10 sm:gap-x-5 sm:gap-y-12 md:grid-cols-3 xl:grid-cols-4 xl:gap-x-6">
                        @foreach ($products as $product)
                            @php
                                $image = $product->getFirstMediaUrl('images');
                                $productSlug = $product->getTranslation('slug', $locale, false)
                                    ?: $product->getTranslation('slug', 'fr', false);
                                $productUrl = $productSlug
                                    ? route('shop.product', ['slug' => $productSlug])
                                    : route('shop.index');
                            @endphp

                            <article class="group min-w-0">
                                <a href="{{ $productUrl }}" class="block">
                                    <div class="aspect-[3/4] overflow-hidden rounded-2xl">
                                    @if ($image)
                                        <img
                                            src="{{ $image }}"
                                            alt="{{ $product->name }}"
                                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="flex h-full items-center justify-center text-zinc-300">
                                            <x-lucide-image-off class="h-6 w-6" />
                                            <span class="sr-only">{{ __('Image indisponible') }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="pt-4">
                                    @if ($product->tag)
                                        <p class="truncate text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                                            {{ $product->tag }}
                                        </p>
                                    @endif

                                    <div class="mt-2 flex items-start justify-between gap-3">
                                        <h2 class="min-w-0 font-['Playfair_Display'] text-lg font-medium leading-tight text-zinc-900 sm:text-xl">
                                            {{ $product->name }}
                                        </h2>

                                        <div class="shrink-0 pt-1 text-xs font-semibold text-zinc-900 sm:text-sm">
                                            @if ($product->price)
                                                {{ number_format((float) $product->price, 2, ',', ' ') }} €
                                            @elseif ($product->price_min && $product->price_max)
                                                {{ number_format((float) $product->price_min, 2, ',', ' ') }}–{{ number_format((float) $product->price_max, 2, ',', ' ') }} €
                                            @elseif ($product->price_min)
                                                {{ __('Dès :price €', ['price' => number_format((float) $product->price_min, 2, ',', ' ')]) }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                </a>
                            </article>
                        @endforeach
                    </div>

                    @if ($products->hasPages())
                        @php
                            $start = max(1, $products->currentPage() - 2);
                            $end = min($products->lastPage(), $products->currentPage() + 2);
                        @endphp

                        <nav aria-label="{{ __('Pagination') }}" class="mt-16 flex items-center justify-center gap-2">
                            @if ($products->onFirstPage())
                                <span class="flex h-10 w-10 items-center justify-center rounded-full text-zinc-300">
                                    <x-lucide-arrow-left class="h-4 w-4" />
                                </span>
                            @else
                                <a
                                    href="{{ $products->previousPageUrl() }}"
                                    class="flex h-10 w-10 items-center justify-center rounded-full text-zinc-600 transition hover:bg-white hover:text-zinc-900"
                                    aria-label="{{ __('Page précédente') }}"
                                >
                                    <x-lucide-arrow-left class="h-4 w-4" />
                                </a>
                            @endif

                            @for ($page = $start; $page <= $end; $page++)
                                <a
                                    href="{{ $products->url($page) }}"
                                    class="flex h-10 w-10 items-center justify-center rounded-full text-sm transition {{ $page === $products->currentPage() ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:bg-white hover:text-zinc-900' }}"
                                    @if ($page === $products->currentPage()) aria-current="page" @endif
                                >
                                    {{ $page }}
                                </a>
                            @endfor

                            @if ($products->hasMorePages())
                                <a
                                    href="{{ $products->nextPageUrl() }}"
                                    class="flex h-10 w-10 items-center justify-center rounded-full text-zinc-600 transition hover:bg-white hover:text-zinc-900"
                                    aria-label="{{ __('Page suivante') }}"
                                >
                                    <x-lucide-arrow-right class="h-4 w-4" />
                                </a>
                            @else
                                <span class="flex h-10 w-10 items-center justify-center rounded-full text-zinc-300">
                                    <x-lucide-arrow-right class="h-4 w-4" />
                                </span>
                            @endif
                        </nav>
                    @endif
                @else
                    <div class="py-24 text-center">
                        <p class="font-['Playfair_Display'] text-3xl text-zinc-900">
                            {{ __('Aucun produit dans cette catégorie pour le moment.') }}
                        </p>
                    </div>
                @endif
            </div>
        </section>

        @if ($currentCategory && ($currentCategory->description || $currentCategory->use))
            <section class="border-t border-zinc-200 px-3 py-16 sm:px-5 sm:py-20 lg:px-8 lg:py-24">
                <div class="mx-auto grid max-w-[1560px] gap-10 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                            {{ __('À propos de la collection') }}
                        </p>

                        <h2 class="mt-4 font-['Playfair_Display'] text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl">
                            {{ $pageTitle }}
                        </h2>
                    </div>

                    <div class="max-w-3xl lg:col-span-7 lg:col-start-6">
                        @if ($currentCategory->description)
                            <div class="text-base leading-8 text-zinc-600 sm:text-lg">
                                {{ $currentCategory->description }}
                            </div>
                        @endif

                        @if ($currentCategory->use)
                            <div class="mt-10 border-t border-zinc-200 pt-8">
                                <h3 class="text-xs font-semibold uppercase tracking-widest text-zinc-900">
                                    {{ __('Utilisation') }}
                                </h3>

                                <p class="mt-4 text-base leading-8 text-zinc-600">
                                    {{ $currentCategory->use }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif
    </main>
</x-layout>
