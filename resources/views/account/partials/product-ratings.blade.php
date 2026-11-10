@php
    $ratingItems = $order->rateableItems();
    $savedRatings = $order->ratings->keyBy('order_item_index');
    $remainingRatings = collect(array_keys($ratingItems))->filter(fn ($index) => ! $savedRatings->has($index))->count();
@endphp

@if ($order->canBeRated() && count($ratingItems) && ($remainingRatings || ($showCompletedRatings ?? false)))
    <section class="rounded-3xl bg-white p-6 sm:p-8" aria-labelledby="ratings-title-{{ $order->id }}">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
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
            class="mt-6" x-data="{ scores: @js($savedRatings->mapWithKeys(fn ($rating) => [$rating->order_item_index => $rating->rating])->all()) }">
            @csrf
            <div class="divide-y divide-zinc-100">
                @foreach ($ratingItems as $index => $item)
                    <div class="flex flex-wrap items-center justify-between gap-x-5 gap-y-3 py-4 first:pt-0">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            @if (filled(data_get($item, 'image')))
                                <img src="{{ data_get($item, 'image') }}" alt="" class="size-14 shrink-0 rounded-xl object-cover" loading="lazy">
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-zinc-900">{{ data_get($item, 'name') }}</p>
                                @foreach (data_get($item, 'options', []) as $option)
                                    <p class="mt-1 text-xs text-zinc-500">{{ data_get($option, 'label') }} · {{ data_get($option, 'display') }}</p>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex items-center gap-0.5" role="group" aria-label="{{ __('Note de :product', ['product' => data_get($item, 'name')]) }}">
                            @for ($star = 1; $star <= 5; $star++)
                                <button type="button" @click="scores[{{ $index }}] = {{ $star }}"
                                    :aria-pressed="Number(scores[{{ $index }}] || 0) === {{ $star }}"
                                    :class="Number(scores[{{ $index }}] || 0) >= {{ $star }} ? 'text-amber-500' : 'text-zinc-300'"
                                    class="flex size-9 items-center justify-center rounded-lg transition hover:text-amber-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-bmlk-brand"
                                    aria-label="{{ __(':count étoiles', ['count' => $star]) }}">
                                    <x-lucide-star class="size-5 fill-current" aria-hidden="true" />
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

            <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
                <p class="text-xs text-zinc-500">{{ __('Vos notes sont privées et servent à améliorer nos produits.') }}</p>
                <button type="submit" :disabled="!Object.values(scores).some(value => Number(value) >= 1)"
                    class="inline-flex items-center justify-center gap-2 rounded-full bg-bmlk-brand px-6 py-3 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40">
                    <x-lucide-check class="size-4" aria-hidden="true" />
                    {{ __('Enregistrer mes notes') }}
                </button>
            </div>
        </form>
    </section>
@endif
