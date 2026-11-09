@if (filled($order->tracking_number))
    @php
        $trackingEvents = \App\Support\ShipmentTimeline::events($order);
        $trackingSteps = \App\Support\ShipmentTimeline::steps($order);
        $lastEvent = $trackingEvents->first(fn ($event) => $event->phase !== 'label_created');
        $trackingLink = is_string($order->tracking_url)
            && filter_var($order->tracking_url, FILTER_VALIDATE_URL)
            && parse_url($order->tracking_url, PHP_URL_SCHEME) === 'https'
                ? $order->tracking_url : null;
    @endphp

    <div class="mt-3" data-creator-faq>
        <details class="group/tracking rounded-2xl bg-bmlk-page/80" data-tracking-details>
            <summary class="flex min-h-16 cursor-pointer list-none items-center gap-3 rounded-2xl px-4 py-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-bmlk-brand [&::-webkit-details-marker]:hidden"
                aria-label="Afficher les étapes et les mises à jour de livraison">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-bmlk-brand">
                    <x-lucide-truck class="size-5" aria-hidden="true" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-xs font-medium text-zinc-500">Numéro de suivi</span>
                    <span class="mt-0.5 block break-all text-sm font-semibold text-zinc-900">{{ $order->tracking_number }}</span>
                    @if ($lastEvent)
                        <span class="mt-1 block text-xs text-bmlk-brand">{{ \App\Support\ShipmentTimeline::label($lastEvent->phase) }}</span>
                    @endif
                </span>
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-white text-bmlk-brand transition-transform duration-300 group-open/tracking:rotate-180">
                    <x-lucide-chevron-down class="size-4" aria-hidden="true" />
                </span>
            </summary>

            <div data-creator-faq-panel class="overflow-hidden">
                <div class="px-4 pb-5 pt-3 sm:px-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-serif text-xl text-zinc-900">Suivi de livraison</h3>
                        @if ($lastEvent && in_array($lastEvent->status_type, ['warning', 'error', 'danger', 'failure'], true))
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">Attention requise</span>
                        @elseif ($lastEvent)
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-bmlk-brand">{{ \App\Support\ShipmentTimeline::label($lastEvent->phase) }}</span>
                        @endif
                    </div>

                    <ol class="mt-5">
                        @foreach ($trackingSteps as $step)
                            <li class="flex min-h-18 gap-3">
                                <div class="flex shrink-0 flex-col items-center">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $step['state'] === 'future' ? 'bg-white text-zinc-300' : ($step['state'] === 'current' ? 'bg-bmlk-brand text-white' : 'bg-emerald-50 text-emerald-700') }}">
                                        @if ($step['state'] === 'done')
                                            <x-lucide-check class="size-5" aria-hidden="true" />
                                        @elseif ($step['state'] === 'current')
                                            <x-lucide-truck class="size-4" aria-hidden="true" />
                                        @else
                                            <x-lucide-circle class="size-4" aria-hidden="true" />
                                        @endif
                                    </span>
                                    @unless ($loop->last)
                                        <span class="my-1 w-0.5 grow rounded-full {{ $step['state'] === 'done' ? 'bg-emerald-200' : 'bg-zinc-200' }}"></span>
                                    @endunless
                                </div>
                                <div class="min-w-0 flex-1 pb-6 pt-1.5">
                                    <p class="text-sm font-semibold {{ $step['state'] === 'future' ? 'text-zinc-400' : 'text-zinc-900' }}">{{ $step['label'] }}</p>
                                    @if ($step['date'])
                                        <p class="mt-1 text-xs text-zinc-500">{{ $step['date']->timezone('Europe/Paris')->translatedFormat('d M Y · H:i') }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    <div class="mt-1">
                        <h4 class="text-sm font-semibold text-zinc-900">Mises à jour du transporteur</h4>
                        @forelse ($trackingEvents as $event)
                            <div class="mt-3 rounded-xl bg-white px-3.5 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                    <p class="text-sm font-semibold text-zinc-900">{{ \App\Support\ShipmentTimeline::label($event->phase) }}</p>
                                    <time class="text-xs tabular-nums text-zinc-500" datetime="{{ $event->event_at->toIso8601String() }}">
                                        {{ $event->event_at->timezone('Europe/Paris')->translatedFormat('d M · H:i') }}
                                    </time>
                                </div>
                                @if ($event->location)
                                    <p class="mt-1 text-xs text-zinc-600">{{ $event->location }}</p>
                                @endif
                                @if ($event->description && $event->phase !== 'label_created')
                                    <p class="mt-1 text-xs leading-5 text-zinc-500">{{ $event->description }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="mt-2 text-sm leading-6 text-zinc-500">Les mises à jour du transporteur apparaîtront ici dès leur réception.</p>
                        @endforelse
                    </div>

                    @if ($order->sendcloud_tracking_synced_at)
                        <p class="mt-4 text-xs text-zinc-500">Actualisé le {{ $order->sendcloud_tracking_synced_at->timezone('Europe/Paris')->translatedFormat('d M Y à H:i') }}</p>
                    @endif

                    @if ($trackingLink)
                        <a href="{{ $trackingLink }}" target="_blank" rel="noopener noreferrer"
                            class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-full bg-white px-4 py-2.5 text-sm font-semibold text-bmlk-brand transition hover:bg-bmlk-soft">
                            Suivre mon colis <x-lucide-arrow-up-right class="size-4" aria-hidden="true" />
                        </a>
                    @endif
                </div>
            </div>
        </details>
    </div>
@endif
