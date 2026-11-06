<x-warehouse.layout title="À préparer">
    <header class="mb-5 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="text-[10px] font-extrabold tracking-[0.19em] text-[#712B47]">BLACK MILK · FRANCE</div>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight">{{ __('À préparer') }}</h1>
        </div>
        <form method="POST" action="{{ route('warehouse.logout') }}">@csrf
            <button type="submit" aria-label="{{ __('Déconnexion') }}" class="flex size-11 items-center justify-center rounded-xl bg-white text-zinc-500 shadow-sm">
                <x-lucide-log-out class="size-5" />
            </button>
        </form>
    </header>

    <form method="GET" action="{{ route('warehouse.orders.index') }}" class="mb-4 flex items-center gap-2 rounded-2xl bg-white px-4 shadow-sm">
        @if ($state !== 'all')<input type="hidden" name="state" value="{{ $state }}">@endif
        <x-lucide-search class="size-5 shrink-0 text-zinc-400" />
        <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Numéro, client, ville…') }}" class="min-h-12 w-full min-w-0 bg-transparent text-base outline-none placeholder:text-zinc-400" aria-label="{{ __('Rechercher une commande') }}">
        @if ($search !== '')
            <a class="text-sm text-[#712B47]" href="{{ route('warehouse.orders.index', ['state' => $state]) }}">{{ __('Effacer') }}</a>
        @endif
    </form>

    <nav aria-label="{{ __('Filtrer les commandes') }}" class="mb-4 flex gap-2 overflow-x-auto pb-1">
        @foreach (['all' => __('Toutes'), 'pending' => __('À faire'), 'processing' => __('En cours')] as $key => $label)
            <a href="{{ route('warehouse.orders.index', array_filter(['state' => $key, 'q' => $search])) }}" class="inline-flex shrink-0 items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold {{ $state === $key ? 'bg-[#712B47] text-white' : 'bg-white text-zinc-600' }}">
                {{ $label }} <span class="{{ $state === $key ? 'text-white' : 'text-zinc-400' }}">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="mb-3 flex items-center justify-between text-sm">
        <span class="font-semibold text-zinc-700">{{ __('Commandes payées') }}</span>
        <span class="text-zinc-500">{{ __('Plus anciennes d’abord') }}</span>
    </div>

    <div class="space-y-3">
        @forelse ($orders as $order)
            @php
                $count = collect($order->items ?? [])->sum(fn (array $item): int => (int) ($item['quantity'] ?? 1));
                $isPending = $order->status === 'pending';
            @endphp
            <a href="{{ route('warehouse.orders.show', $order->order_number) }}" class="block rounded-2xl bg-white p-4 shadow-sm transition active:scale-[0.99]">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-extrabold tracking-tight">{{ $order->order_number }}</span>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">{{ __('Payée') }}</span>
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $isPending ? 'bg-amber-50 text-amber-800' : 'bg-sky-50 text-sky-800' }}">{{ $isPending ? __('À faire') : __('En cours') }}</span>
                </div>
                <div class="mt-3 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="truncate font-bold">{{ trim($order->first_name.' '.($order->last_name ?? '')) }}</div>
                        <div class="mt-0.5 text-sm text-zinc-500">{{ $order->city }} · {{ $order->shipping_option_name ?: __('Livraison') }}</div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2 text-[#712B47]">
                        <span class="text-sm font-bold">{{ number_format((float) $order->total_amount, 2, ',', ' ') }} €</span>
                        <x-lucide-chevron-right class="size-4" />
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-x-3 gap-y-2">
                    @foreach (array_slice($order->items ?? [], 0, 4) as $item)
                        <div class="flex min-w-0 max-w-full items-center gap-2">
                            @if (filled(data_get($item, 'image')))
                                <img src="{{ data_get($item, 'image') }}" loading="lazy" alt="" class="size-11 shrink-0 rounded-lg bg-zinc-100 object-contain">
                            @else
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-zinc-100"><x-lucide-package class="size-5 text-zinc-400" /></span>
                            @endif
                            <span class="max-w-32 text-xs font-medium leading-snug text-zinc-700"><span class="font-extrabold text-[#712B47]">{{ (int) data_get($item, 'quantity', 1) }}×</span> {{ data_get($item, 'name', __('Produit')) }}</span>
                        </div>
                    @endforeach
                    @if (count($order->items ?? []) > 4)
                        <span class="self-center text-xs font-semibold text-zinc-500">+{{ count($order->items) - 4 }}</span>
                    @endif
                </div>
                <div class="mt-3 flex items-center justify-between gap-2 text-xs text-zinc-500">
                    <span>{{ $count }} {{ __('articles') }}</span>
                    <span>{{ $order->created_at->timezone('Europe/Paris')->format('d/m · H:i') }}</span>
                </div>
            </a>
        @empty
            <div class="rounded-2xl bg-white px-5 py-14 text-center shadow-sm">
                <x-lucide-check-check class="mx-auto size-10 text-emerald-600" />
                <p class="mt-3 font-bold">{{ __('Aucune commande à préparer') }}</p>
                <p class="mt-1 text-sm text-zinc-500">{{ __('Tout est à jour.') }}</p>
            </div>
        @endforelse
    </div>

    @if ($orders->hasPages())
        <div class="mt-5">{{ $orders->links() }}</div>
    @endif

    <div class="fixed inset-x-0 bottom-0 z-20 bg-white/95 px-5 pb-[max(0.9rem,env(safe-area-inset-bottom))] pt-3 shadow-lg backdrop-blur-sm">
        <div class="mx-auto flex max-w-xl items-center justify-between gap-4">
            <div class="flex items-center gap-2 font-semibold text-[#712B47]"><x-lucide-package-check class="size-5" /> {{ __('Commandes') }}</div>
            <span class="rounded-full bg-[#F5E9EE] px-3 py-2 text-sm font-bold text-[#712B47]">{{ $counts['all'] }} {{ __('à traiter') }}</span>
        </div>
    </div>
</x-warehouse.layout>
