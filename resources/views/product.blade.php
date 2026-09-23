<x-layout
    :title="$metaTitle"
    :meta-description="$metaDescription"
    :canonical="$canonical"
    og-type="product"
    :og-image="$ogImage"
>
    @vite('resources/js/product.js')

    @php
        $locale = in_array(app()->getLocale(), ['fr', 'en', 'ro'], true)
            ? app()->getLocale()
            : 'fr';

        $localeKey = strtoupper($locale);

        $productName = $product->getTranslation('name', $locale, false)
            ?: $product->getTranslation('name', 'fr', false);

        $productTag = $product->getTranslation('tag', $locale, false)
            ?: $product->getTranslation('tag', 'fr', false);

        $productDescription = $product->getTranslation('description', $locale, false)
            ?: $product->getTranslation('description', 'fr', false);

        $categoryName = $product->category?->getTranslation('name', $locale, false)
            ?: $product->category?->getTranslation('name', 'fr', false);

        $categorySlug = $product->category?->getTranslation('slug', $locale, false)
            ?: $product->category?->getTranslation('slug', 'fr', false);

        $categoryDescription = $product->category?->getTranslation('description', $locale, false)
            ?: $product->category?->getTranslation('description', 'fr', false);

        $categoryUse = $product->category?->getTranslation('use', $locale, false)
            ?: $product->category?->getTranslation('use', 'fr', false);

        $categoryUrl = $categorySlug
            ? route('shop.category', ['slug' => $categorySlug])
            : route('shop.index');

        $details = $product->getTranslation('details', $locale, false);

        if (blank($details)) {
            $details = $product->getTranslation('details', 'fr', false);
        }

        $details = is_array($details) ? $details : [];

        $contents = $product->getTranslation('contents', $locale, false);

        if (blank($contents)) {
            $contents = $product->getTranslation('contents', 'fr', false);
        }

        $contents = is_array($contents) ? $contents : [];

        $images = $product->getMedia('images')
            ->map(fn ($media) => [
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
            ])
            ->values();

        $sizeLabel = data_get($product->size, 'raw');

        $priceLabel = null;

        if ($product->price !== null) {
            $priceLabel = number_format((float) $product->price, 2, ',', ' ').' €';
        } elseif ($product->price_min !== null && $product->price_max !== null) {
            $priceLabel = number_format((float) $product->price_min, 2, ',', ' ')
                .'–'
                .number_format((float) $product->price_max, 2, ',', ' ')
                .' €';
        } elseif ($product->price_min !== null) {
            $priceLabel = __('Dès :price €', [
                'price' => number_format((float) $product->price_min, 2, ',', ' '),
            ]);
        }

        $productOptions = collect($product->options ?? [])
            ->filter(fn (mixed $option): bool => is_array($option) && filled(data_get($option, 'id')))
            ->map(function (array $option) use ($localeKey): array {
                $id = data_get($option, 'id');
                $label = data_get($option, "label.{$localeKey}")
                    ?: data_get($option, 'label.FR')
                    ?: $id;

                $choices = collect(data_get($option, 'values', []))
                    ->map(function (mixed $value): array {
                        if (is_array($value)) {
                            $quantity = data_get($value, 'quantity');
                            $price = data_get($value, 'price.amount');

                            return [
                                'key' => 'quantity:'.($quantity ?? 'none').':'.($price ?? 'none'),
                                'value' => null,
                                'quantity' => $quantity !== null ? (int) $quantity : null,
                                'price' => $price !== null ? (float) $price : null,
                                'display' => $quantity !== null ? $quantity.' ×' : __('Option'),
                            ];
                        }

                        return [
                            'key' => 'value:'.(string) $value,
                            'value' => (string) $value,
                            'quantity' => null,
                            'price' => null,
                            'display' => (string) $value,
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'id' => $id,
                    'label' => $label,
                    'choices' => $choices,
                ];
            })
            ->filter(fn (array $option): bool => $option['choices'] !== [])
            ->values()
            ->all();

        $cartProduct = [
            'id' => $product->id,
            'name' => $productName,
            'url' => $canonical,
            'image' => $product->getFirstMediaUrl('images', 'thumb') ?: $product->getFirstMediaUrl('images'),
            'price' => $product->price !== null ? (float) $product->price : null,
        ];

        $offerSchema = null;

        if ($product->price !== null) {
            $offerSchema = [
                '@type' => 'Offer',
                'url' => $canonical,
                'priceCurrency' => 'EUR',
                'price' => (float) $product->price,
            ];
        } elseif ($product->price_min !== null || $product->price_max !== null) {
            $offerSchema = array_filter([
                '@type' => 'AggregateOffer',
                'url' => $canonical,
                'priceCurrency' => 'EUR',
                'lowPrice' => $product->price_min !== null ? (float) $product->price_min : null,
                'highPrice' => $product->price_max !== null ? (float) $product->price_max : null,
            ], fn ($value) => $value !== null);
        }

        $additionalProperties = collect($product->claims ?? [])
            ->map(fn ($claim) => [
                '@type' => 'PropertyValue',
                'name' => __('Caractéristique'),
                'value' => $claim,
            ])
            ->values()
            ->all();

        if ($sizeLabel) {
            $additionalProperties[] = [
                '@type' => 'PropertyValue',
                'name' => __('Format'),
                'value' => $sizeLabel,
            ];
        }

        $productSchema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => $canonical.'#product',
            'url' => $canonical,
            'name' => $productName,
            'description' => $metaDescription,
            'image' => $images->pluck('url')->all(),
            'sku' => $product->sku,
            'brand' => [
                '@type' => 'Brand',
                'name' => 'BLACK MILK',
            ],
            'category' => $categoryName,
            'offers' => $offerSchema,
            'additionalProperty' => $additionalProperties ?: null,
        ], fn ($value) => $value !== null && $value !== [] && $value !== '');

        $breadcrumbItems = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => __('Accueil'),
                'item' => route('home'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => __('Boutique'),
                'item' => route('shop.index'),
            ],
        ];

        if ($categoryName && $categorySlug) {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $categoryName,
                'item' => $categoryUrl,
            ];
        }

        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => count($breadcrumbItems) + 1,
            'name' => $productName,
            'item' => $canonical,
        ];

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbItems,
        ];
    @endphp

    <script type="application/ld+json">
        {!! json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    <script type="application/ld+json">
        {!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('blackMilkProduct', (product, options, fallbackPriceLabel, numberLocale) => ({
                qty: 1,
                selected: {},

                init() {
                    options.forEach(option => {
                        if (option.choices.length === 1) {
                            this.selectOption(option.id, option.label, option.choices[0]);
                        }
                    });
                },

                selectOption(optionId, optionLabel, choice) {
                    this.selected = {
                        ...this.selected,
                        [optionId]: {
                            ...choice,
                            id: optionId,
                            label: optionLabel,
                        },
                    };
                },

                isSelected(optionId, choiceKey) {
                    return this.selected[optionId]?.key === choiceKey;
                },

                get allOptionsSelected() {
                    return options.every(option => Boolean(this.selected[option.id]));
                },

                get currentPrice() {
                    const pricedOption = Object.values(this.selected)
                        .find(option => option.price !== null && option.price !== undefined);

                    if (pricedOption) {
                        return Number(pricedOption.price);
                    }

                    return product.price !== null && product.price !== undefined
                        ? Number(product.price)
                        : null;
                },

                get isReady() {
                    return this.allOptionsSelected
                        && this.currentPrice !== null
                        && Number.isFinite(Number(this.currentPrice));
                },

                get displayPrice() {
                    if (this.currentPrice === null) {
                        return fallbackPriceLabel || '';
                    }

                    return new Intl.NumberFormat(numberLocale, {
                        style: 'currency',
                        currency: 'EUR',
                    }).format(this.currentPrice);
                },

                addToCart() {
                    if (! this.isReady) {
                        return;
                    }

                    this.$store.cart.addItem(product, this.selected, this.qty);
                },
            }));
        });
    </script>

    <main>
        <section class="px-3 pb-16 pt-10 sm:px-5 sm:pb-20 sm:pt-14 lg:px-8 lg:pb-24 lg:pt-16">
            <div class="mx-auto max-w-[1560px]">
                <nav
                    aria-label="{{ __('Fil d’Ariane') }}"
                    class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-widest text-zinc-500"
                >
                    <a href="{{ route('home') }}" class="transition hover:text-zinc-900">
                        {{ __('Accueil') }}
                    </a>

                    <x-lucide-chevron-right class="h-3 w-3" />

                    <a href="{{ route('shop.index') }}" class="transition hover:text-zinc-900">
                        {{ __('Boutique') }}
                    </a>

                    @if ($categoryName)
                        <x-lucide-chevron-right class="h-3 w-3" />

                        <a href="{{ $categoryUrl }}" class="transition hover:text-zinc-900">
                            {{ $categoryName }}
                        </a>
                    @endif

                    <x-lucide-chevron-right class="h-3 w-3" />

                    <span class="text-zinc-900">{{ $productName }}</span>
                </nav>

                <div class="mt-8 grid gap-10 lg:grid-cols-12 lg:gap-14 xl:gap-20">
                    <div class="lg:col-span-7" data-product-gallery>
                        <div class="relative">
                            <div
                                class="overflow-hidden rounded-3xl bg-white"
                                data-embla-viewport
                                role="region"
                                aria-roledescription="{{ __('carrousel') }}"
                                aria-label="{{ __('Galerie de :product', ['product' => $productName]) }}"
                            >
                                <div class="flex touch-pan-y">
                                @if ($images->isNotEmpty())
                                    @foreach ($images as $index => $image)
                                        <div
                                            class="relative aspect-square min-w-0 flex-[0_0_100%]"
                                            role="group"
                                            aria-roledescription="{{ __('diapositive') }}"
                                            aria-label="{{ ($index + 1).' / '.$images->count() }}"
                                        >
                                            <img
                                                src="{{ $image['url'] }}"
                                                alt="{{ $productName }}{{ $images->count() > 1 ? ' — '.($index + 1) : '' }}"
                                                class="h-full w-full select-none object-contain p-6 sm:p-10 lg:p-12"
                                                draggable="false"
                                                @if ($index === 0)
                                                    fetchpriority="high"
                                                @else
                                                    loading="lazy"
                                                @endif
                                            >

                                            @if ($product->is_new && $index === 0)
                                                <span class="absolute left-5 top-5 rounded-full bg-[#DDA1AA] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-zinc-900">
                                                    {{ __('Nouveau') }}
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <div class="flex aspect-square min-w-0 flex-[0_0_100%] items-center justify-center text-zinc-300">
                                        <x-lucide-image-off class="h-8 w-8" />
                                        <span class="sr-only">{{ __('Image indisponible') }}</span>
                                    </div>
                                @endif
                                </div>
                            </div>

                            @if ($images->count() > 1)
                                <div class="pointer-events-none absolute inset-x-0 top-1/2 flex -translate-y-1/2 items-center justify-between px-4 sm:px-5">
                                    <button
                                        type="button"
                                        data-embla-prev
                                        class="pointer-events-auto flex h-11 w-11 items-center justify-center rounded-full bg-white/90 text-zinc-900 shadow-sm backdrop-blur transition hover:bg-white disabled:pointer-events-none disabled:opacity-30"
                                        aria-label="{{ __('Image précédente') }}"
                                    >
                                        <x-lucide-chevron-left class="h-5 w-5" />
                                    </button>

                                    <button
                                        type="button"
                                        data-embla-next
                                        class="pointer-events-auto flex h-11 w-11 items-center justify-center rounded-full bg-white/90 text-zinc-900 shadow-sm backdrop-blur transition hover:bg-white disabled:pointer-events-none disabled:opacity-30"
                                        aria-label="{{ __('Image suivante') }}"
                                    >
                                        <x-lucide-chevron-right class="h-5 w-5" />
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if ($images->count() > 1)
                            <div class="-mx-1 mt-3 flex gap-3 overflow-x-auto px-1 py-1 pb-2" aria-label="{{ __('Miniatures du produit') }}">
                                @foreach ($images as $index => $image)
                                    <button
                                        type="button"
                                        data-embla-thumb
                                        class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl bg-white transition {{ $index === 0 ? 'ring-2 ring-[#DDA1AA]' : 'ring-1 ring-zinc-200 hover:ring-zinc-400' }}"
                                        aria-label="{{ __('Voir l’image :number', ['number' => $index + 1]) }}"
                                        aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                                    >
                                        <img
                                            src="{{ $image['thumb'] }}"
                                            alt=""
                                            class="h-full w-full select-none object-cover"
                                            draggable="false"
                                            loading="lazy"
                                        >
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div
                        class="lg:col-span-5"
                        x-data="blackMilkProduct(
                            @js($cartProduct),
                            @js($productOptions),
                            @js($priceLabel),
                            @js(match ($locale) {
                                'en' => 'en-GB',
                                'ro' => 'ro-RO',
                                default => 'fr-FR',
                            })
                        )"
                    >
                        <div class="lg:sticky lg:top-8">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($categoryName)
                                    <a
                                        href="{{ $categoryUrl }}"
                                        class="rounded-full bg-[#EFDDE0] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[#A9636F] transition hover:bg-[#DDA1AA] hover:text-zinc-900"
                                    >
                                        {{ $categoryName }}
                                    </a>
                                @endif

                                @if ($productTag)
                                    <span class="text-xs font-semibold uppercase tracking-widest text-zinc-500">
                                        {{ $productTag }}
                                    </span>
                                @endif
                            </div>

                            <h1 class="mt-6 font-['Playfair_Display'] text-5xl font-medium leading-none tracking-tight text-zinc-900 sm:text-6xl">
                                {{ $productName }}
                            </h1>

                            @if ($priceLabel || $productOptions !== [])
                                <div class="mt-6 flex flex-wrap items-baseline gap-3">
                                    <p
                                        class="font-['Playfair_Display'] text-3xl font-medium text-zinc-900"
                                        x-text="displayPrice || @js(__('Sélectionnez les options'))"
                                    >{{ $priceLabel ?: __('Sélectionnez les options') }}</p>

                                    @if ($product->compare_at_price !== null && $product->price !== null && (float) $product->compare_at_price > (float) $product->price)
                                        <p class="text-sm text-zinc-400 line-through">
                                            {{ number_format((float) $product->compare_at_price, 2, ',', ' ') }} €
                                        </p>
                                    @endif
                                </div>
                            @endif

                            @if ($productDescription)
                                <p class="mt-6 max-w-xl text-base leading-8 text-zinc-600 sm:text-lg">
                                    {{ $productDescription }}
                                </p>
                            @elseif ($categoryDescription)
                                <p class="mt-6 max-w-xl text-base leading-8 text-zinc-600 sm:text-lg">
                                    {{ __(':product appartient à la collection :category. :description', [
                                        'product' => $productName,
                                        'category' => $categoryName,
                                        'description' => $categoryDescription,
                                    ]) }}
                                </p>
                            @endif

                            @if (($product->claims ?? []) !== [] || $sizeLabel)
                                <div class="mt-7 flex flex-wrap gap-2">
                                    @foreach ($product->claims ?? [] as $claim)
                                        <span class="rounded-full bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-zinc-700">
                                            {{ $claim }}
                                        </span>
                                    @endforeach

                                    @if ($sizeLabel)
                                        <span class="rounded-full bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-zinc-700">
                                            {{ $sizeLabel }}
                                        </span>
                                    @endif
                                </div>
                            @endif

                            @if ($productOptions !== [])
                                <div class="mt-9 space-y-6">
                                    @foreach ($productOptions as $option)
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-widest text-zinc-500">
                                                {{ $option['label'] }}
                                            </p>

                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @foreach ($option['choices'] as $choice)
                                                    <button
                                                        type="button"
                                                        @click="selectOption(
                                                            @js($option['id']),
                                                            @js($option['label']),
                                                            @js($choice)
                                                        )"
                                                        :class="isSelected(@js($option['id']), @js($choice['key']))
                                                            ? 'border-[#A9636F] bg-[#EFDDE0] text-zinc-900'
                                                            : 'border-zinc-200 bg-white text-zinc-700 hover:border-[#DDA1AA]'"
                                                        class="rounded-full border px-4 py-2 text-sm transition"
                                                    >
                                                        <span>{{ $choice['display'] }}</span>

                                                        @if ($choice['price'] !== null)
                                                            <span class="ml-1 text-xs text-zinc-500">
                                                                · {{ number_format((float) $choice['price'], 2, ',', ' ') }} €
                                                            </span>
                                                        @endif
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                                <div class="flex h-13 items-center justify-between rounded-full bg-white px-2 sm:w-36">
                                    <button
                                        type="button"
                                        @click="qty = Math.max(1, qty - 1)"
                                        class="flex h-10 w-10 items-center justify-center rounded-full text-[#A9636F] transition hover:bg-[#F3E4E7]"
                                        aria-label="{{ __('Réduire la quantité') }}"
                                    >
                                        <x-lucide-minus class="h-4 w-4" />
                                    </button>

                                    <span class="min-w-8 text-center text-sm font-semibold" x-text="qty"></span>

                                    <button
                                        type="button"
                                        @click="qty++"
                                        class="flex h-10 w-10 items-center justify-center rounded-full text-[#A9636F] transition hover:bg-[#F3E4E7]"
                                        aria-label="{{ __('Augmenter la quantité') }}"
                                    >
                                        <x-lucide-plus class="h-4 w-4" />
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    @click="addToCart()"
                                    :disabled="! isReady"
                                    :class="isReady
                                        ? 'bg-[#A9636F] text-white hover:bg-[#945763]'
                                        : 'cursor-not-allowed bg-zinc-200 text-zinc-400'"
                                    class="flex h-13 flex-1 items-center justify-center gap-2 rounded-full px-7 text-xs font-semibold uppercase tracking-widest transition"
                                >
                                    <x-lucide-shopping-bag class="h-4 w-4" />
                                    <span x-text="isReady ? @js(__('Ajouter au panier')) : @js(__('Choisissez les options'))">
                                        {{ $productOptions === [] ? __('Ajouter au panier') : __('Choisissez les options') }}
                                    </span>
                                </button>
                            </div>

                            <div class="mt-5 grid gap-3 text-sm leading-6 text-zinc-500 sm:grid-cols-2">
                                <div class="flex gap-3">
                                    <x-lucide-package-check class="mt-1 h-4 w-4 shrink-0 text-[#A9636F]" />
                                    <span>{{ __('Expédition sous 1 à 3 jours ouvrés') }}</span>
                                </div>

                                <div class="flex gap-3">
                                    <x-lucide-shield-check class="mt-1 h-4 w-4 shrink-0 text-[#A9636F]" />
                                    <span>{{ __('Produit professionnel BLACK MILK') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if ($details !== [] || $contents !== [] || $categoryUse)
            <section class="border-t border-zinc-200 px-3 py-16 sm:px-5 sm:py-20 lg:px-8 lg:py-24">
                <div class="mx-auto grid max-w-[1560px] gap-10 lg:grid-cols-12 lg:gap-16">
                    <div class="lg:col-span-4">
                        <p class="text-xs font-semibold uppercase tracking-widest text-[#A9636F]">
                            {{ __('Détails du produit') }}
                        </p>

                        <h2 class="mt-4 font-['Playfair_Display'] text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl">
                            {{ __('Tout ce qu’il faut savoir') }}
                        </h2>
                    </div>

                    <div class="lg:col-span-7 lg:col-start-6">
                        @if ($contents !== [])
                            <div class="mb-10 rounded-3xl bg-white p-6 sm:p-8">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#EFDDE0] text-[#A9636F]">
                                        <x-lucide-package-open class="h-4 w-4" />
                                    </span>

                                    <h3 class="font-['Playfair_Display'] text-2xl font-medium text-zinc-900">
                                        {{ __('Contenu') }}
                                    </h3>
                                </div>

                                <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                                    @foreach ($contents as $item)
                                        <li class="flex gap-3 text-sm leading-6 text-zinc-600">
                                            <x-lucide-check class="mt-1 h-4 w-4 shrink-0 text-[#A9636F]" />
                                            <span>{{ is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="border-t border-zinc-200">
                            @foreach ($details as $detail)
                                @php
                                    $detailTitle = data_get($detail, 'title');
                                    $detailBody = data_get($detail, 'body');
                                @endphp

                                @if ($detailTitle || $detailBody)
                                    <details class="group border-b border-zinc-200">
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-6">
                                            <h3 class="text-sm font-semibold uppercase tracking-widest text-zinc-900">
                                                {{ $detailTitle ?: __('Informations') }}
                                            </h3>

                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#EFDDE0] text-[#A9636F]">
                                                <x-lucide-plus class="h-4 w-4 group-open:hidden" />
                                                <x-lucide-minus class="hidden h-4 w-4 group-open:block" />
                                            </span>
                                        </summary>

                                        @if ($detailBody)
                                            <div class="max-w-3xl whitespace-pre-line pb-7 text-base leading-8 text-zinc-600">
                                                {{ $detailBody }}
                                            </div>
                                        @endif
                                    </details>
                                @endif
                            @endforeach

                            @if ($categoryUse)
                                <details class="group border-b border-zinc-200">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-6">
                                        <h3 class="text-sm font-semibold uppercase tracking-widest text-zinc-900">
                                            {{ __('Utilisation') }}
                                        </h3>

                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#EFDDE0] text-[#A9636F]">
                                            <x-lucide-plus class="h-4 w-4 group-open:hidden" />
                                            <x-lucide-minus class="hidden h-4 w-4 group-open:block" />
                                        </span>
                                    </summary>

                                    <div class="max-w-3xl whitespace-pre-line pb-7 text-base leading-8 text-zinc-600">
                                        {{ $categoryUse }}
                                    </div>
                                </details>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($recommended->isNotEmpty())
            <section class="px-3 py-8 sm:px-5 sm:py-10 lg:px-8 lg:py-12">
                <div class="mx-auto max-w-[1560px] overflow-hidden rounded-3xl bg-[#DDA1AA] px-6 py-12 sm:px-10 sm:py-14 lg:px-14 lg:py-16 xl:px-16">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-zinc-700">
                                {{ __('Sélection BLACK MILK') }}
                            </p>

                            <h2 class="mt-4 font-['Playfair_Display'] text-4xl font-medium leading-none tracking-tight text-zinc-900 sm:text-5xl">
                                {{ __('Tu pourrais') }}
                                <span class="italic text-white">{{ __('aussi aimer') }}</span>
                            </h2>
                        </div>

                        <a
                            href="{{ $categoryUrl }}"
                            class="group inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-zinc-900"
                        >
                            {{ __('Voir la collection') }}
                            <x-lucide-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </a>
                    </div>

                    <div class="-mx-6 mt-10 flex snap-x snap-mandatory gap-4 overflow-x-auto px-6 pb-2 sm:-mx-10 sm:px-10 lg:mx-0 lg:grid lg:grid-cols-4 lg:gap-5 lg:overflow-visible lg:px-0">
                        @foreach ($recommended as $recommendedProduct)
                            @php
                                $recommendedSlug = $recommendedProduct->getTranslation('slug', $locale, false)
                                    ?: $recommendedProduct->getTranslation('slug', 'fr', false);

                                $recommendedUrl = $recommendedSlug
                                    ? route('shop.product', ['slug' => $recommendedSlug])
                                    : route('shop.index');

                                $recommendedImage = $recommendedProduct->getFirstMediaUrl('images');
                            @endphp

                            <article class="group/card w-3/4 shrink-0 snap-start sm:w-2/5 lg:w-auto">
                                <a href="{{ $recommendedUrl }}" class="block">
                                    <div class="aspect-[3/4] overflow-hidden rounded-2xl">
                                        @if ($recommendedImage)
                                            <img
                                                src="{{ $recommendedImage }}"
                                                alt="{{ $recommendedProduct->name }}"
                                                class="h-full w-full object-cover transition duration-500 group-hover/card:scale-105"
                                                loading="lazy"
                                            >
                                        @endif
                                    </div>

                                    <div class="pt-4">
                                        @if ($recommendedProduct->tag)
                                            <p class="truncate text-xs font-semibold uppercase tracking-widest text-zinc-700">
                                                {{ $recommendedProduct->tag }}
                                            </p>
                                        @endif

                                        <div class="mt-2 flex items-start justify-between gap-3">
                                            <h3 class="font-['Playfair_Display'] text-xl font-medium leading-tight text-zinc-900">
                                                {{ $recommendedProduct->name }}
                                            </h3>

                                            <div class="shrink-0 pt-1 text-sm font-semibold text-zinc-900">
                                                @if ($recommendedProduct->price)
                                                    {{ number_format((float) $recommendedProduct->price, 2, ',', ' ') }} €
                                                @elseif ($recommendedProduct->price_min && $recommendedProduct->price_max)
                                                    {{ number_format((float) $recommendedProduct->price_min, 2, ',', ' ') }}–{{ number_format((float) $recommendedProduct->price_max, 2, ',', ' ') }} €
                                                @elseif ($recommendedProduct->price_min)
                                                    {{ __('Dès :price €', ['price' => number_format((float) $recommendedProduct->price_min, 2, ',', ' ')]) }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </main>
</x-layout>
