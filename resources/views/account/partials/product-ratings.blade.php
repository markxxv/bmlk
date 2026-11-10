@php
    $ratingItems = $order->rateableItems();
    $savedRatings = $order->ratings->keyBy('order_item_index');
    $initialScores = $savedRatings->mapWithKeys(fn ($rating) => [$rating->order_item_index => $rating->rating])->all();
    $remainingRatings = collect(array_keys($ratingItems))->filter(fn ($index) => ! $savedRatings->has($index))->count();
@endphp

@if ($order->canBeRated() && count($ratingItems) && ($remainingRatings || ($showCompletedRatings ?? false)))
    <section class="rounded-3xl bg-white p-6 sm:p-8" aria-labelledby="ratings-title-{{ $order->id }}">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <span class="inline-flex items-center gap-2 text-xs font-medium text-bmlk-brand">
                    <x-lucide-star class="size-4" aria-hidden="true" />
                    {{ __('Votre avis compte') }}
                </span>
                <h2 id="ratings-title-{{ $order->id }}" class="mt-2 font-serif text-2xl text-zinc-900">
                    {{ $remainingRatings ? __('Évaluez vos produits') : __('Vos évaluations') }}
                </h2>
                <p class="mt-2 text-sm leading-6 text-zinc-500">
                    {{ $remainingRatings
                        ? __('Votre commande :number a été livrée. Notez chaque produit de 1 à 5 étoiles. Vous pouvez revenir plus tard pour terminer.', ['number' => $order->order_number])
                        : __('Vous pouvez modifier vos notes à tout moment.') }}
                </p>
            </div>
            @if ($remainingRatings)
                <span class="rounded-full bg-bmlk-soft px-3 py-1.5 text-xs font-medium text-bmlk-brand">
                    {{ __(':done sur :total notés', ['done' => count($ratingItems) - $remainingRatings, 'total' => count($ratingItems)]) }}
                </span>
            @endif
        </div>

        <form method="POST" action="{{ route('account.ratings.store', $order) }}"
            class="mt-5 sm:mt-6" x-data="{ scores: @js($initialScores) }">
            @csrf
            <div class="divide-y divide-zinc-100">
                @foreach ($ratingItems as $index => $item)
                    @php
                        $product = ($ratingProducts ?? collect())->get((int) data_get($item, 'product_id'));
                        // Current Spatie media first. Historical checkout URLs may be obsolete.
                        $image = $product
                            ? $product->storefrontImageUrl('thumb')
                            : (string) data_get($item, 'image', '');
                    @endphp
                    <div class="flex min-w-0 flex-col gap-3 py-4 first:pt-0 sm:flex-row sm:items-center sm:justify-between sm:gap-5">
                        <div class="flex w-full min-w-0 items-center gap-3 sm:w-auto sm:flex-1">
                            <div class="size-16 shrink-0 overflow-hidden rounded-xl bg-bmlk-page"
                                x-data="{ imageError: false }">
                                @if (filled($image))
                                    <img src="{{ $image }}" alt="" loading="lazy"
                                        x-show="!imageError" x-on:error="imageError = true"
                                        class="size-full object-cover">
                                @endif
                                <span x-show="{{ filled($image) ? 'imageError' : 'true' }}" x-cloak
                                    class="flex size-full items-center justify-center text-bmlk-brand">
                                    <x-lucide-package class="size-6" aria-hidden="true" />
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="break-words text-sm font-medium leading-5 text-zinc-900">{{ data_get($item, 'name') }}</p>
                                @foreach (data_get($item, 'options', []) as $option)
                                    <p class="mt-1 break-words text-xs text-zinc-500">
                                        {{ data_get($option, 'label') }} · {{ data_get($option, 'display') }}
                                    </p>
                                @endforeach
                            </div>
                        </div>

                        <div x-data="{ hovered: 0 }" @mouseleave="hovered = 0"
                            class="flex w-full shrink-0 items-center justify-center gap-0.5 rounded-xl bg-bmlk-page px-1 py-1.5 sm:w-auto sm:justify-end sm:bg-transparent sm:p-0"
                            role="group" aria-label="{{ __('Note de :product', ['product' => data_get($item, 'name')]) }}">
                            @for ($star = 1; $star <= 5; $star++)
                                <button type="button" @click="scores[{{ $index }}] = {{ $star }}"
                                    @mouseenter="hovered = {{ $star }}"
                                    @focus="hovered = {{ $star }}" @blur="hovered = 0"
                                    :aria-pressed="Number(scores[{{ $index }}] || 0) === {{ $star }}"
                                    :class="(hovered || Number(scores[{{ $index }}] || 0)) >= {{ $star }} ? 'text-amber-500' : 'text-zinc-300'"
                                    class="flex size-11 shrink-0 items-center justify-center rounded-lg transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-bmlk-brand sm:size-10"
                                    aria-label="{{ __(':count étoiles', ['count' => $star]) }}">
                                    <x-lucide-star class="size-6 fill-current sm:size-5" aria-hidden="true" />
                                </button>
                            @endfor
                        </div>
                        <input type="hidden" name="ratings[{{ $index }}]" :value="scores[{{ $index }}] ?? ''">
                    </div>
                @endforeach
            </div>

            @error('ratings')
                <p class="mt-3 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror

            <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-zinc-500">{{ __('Vos notes sont privées et servent à améliorer nos produits.') }}</p>
                <button type="submit" :disabled="!Object.values(scores).some(value => Number(value) >= 1)"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-bmlk-brand px-6 py-3 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto">
                    <x-lucide-check class="size-4" aria-hidden="true" />
                    {{ __('Enregistrer mes notes') }}
                </button>
            </div>
        </form>
    </section>
@endif
