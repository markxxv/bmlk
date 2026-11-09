<x-warehouse.layout title="To prepare">
    <header class="mb-5 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="text-[10px] font-extrabold tracking-[0.19em] text-[#712B47]">BLACK MILK · FRANCE</div>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight">{{ __('To prepare') }}</h1>
        </div>
        <form method="POST" action="{{ route('warehouse.logout') }}">@csrf
            <button type="submit" aria-label="{{ __('Sign out') }}" class="flex size-11 items-center justify-center rounded-xl bg-white text-zinc-500 shadow-sm">
                <x-lucide-log-out class="size-5" />
            </button>
        </form>
    </header>

    <form method="GET" action="{{ route('warehouse.orders.index') }}" class="mb-4 flex items-center gap-2 rounded-2xl bg-white px-4 shadow-sm">
        @if ($state !== 'all')<input type="hidden" name="state" value="{{ $state }}">@endif
        @if ($sort !== 'oldest')<input type="hidden" name="sort" value="{{ $sort }}">@endif
        <x-lucide-search class="size-5 shrink-0 text-zinc-400" />
        <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Order number, customer, city…') }}" class="min-h-12 w-full min-w-0 bg-transparent text-base outline-none placeholder:text-zinc-400" aria-label="{{ __('Search orders') }}">
        @if ($search !== '')
            <a class="text-sm text-[#712B47]" href="{{ route('warehouse.orders.index', ['state' => $state, 'sort' => $sort]) }}">{{ __('Clear') }}</a>
        @endif
    </form>

    <nav aria-label="{{ __('Filter orders') }}" class="mb-4 flex gap-2 overflow-x-auto pb-1">
        @foreach (['all' => __('All'), 'pending' => __('To prepare'), 'processing' => __('In progress')] as $key => $label)
            <a href="{{ route('warehouse.orders.index', array_filter(['state' => $key, 'q' => $search, 'sort' => $sort !== 'oldest' ? $sort : null])) }}" class="inline-flex shrink-0 items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold {{ $state === $key ? 'bg-[#712B47] text-white' : 'bg-white text-zinc-600' }}">
                {{ $label }} <span class="{{ $state === $key ? 'text-white' : 'text-zinc-400' }}">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="mb-3 flex items-center justify-between gap-3 px-1">
        <div class="flex min-w-0 items-center gap-2 text-xs font-semibold text-zinc-700">
            <span class="size-1.5 shrink-0 rounded-full bg-emerald-500"></span>
            <span>{{ __('Paid orders') }}</span>
        </div>
        <a href="{{ route('warehouse.orders.index', array_filter(['state' => $state, 'q' => $search, 'sort' => $sort === 'oldest' ? 'newest' : null])) }}"
           class="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-lg px-2 text-xs text-zinc-500 transition hover:bg-white hover:text-[#712B47] focus-visible:outline-2 focus-visible:outline-[#712B47]"
           aria-label="{{ __('Reverse sort order') }}">
            @if ($sort === 'oldest')
                <x-lucide-arrow-down-narrow-wide class="size-4" />
                <span>{{ __('Oldest first') }}</span>
            @else
                <x-lucide-arrow-down-wide-narrow class="size-4" />
                <span>{{ __('Newest first') }}</span>
            @endif
        </a>
    </div>

    <div class="space-y-3">
        @forelse ($orders as $order)
            @php
                $items = $order->items ?? [];
                $reservedRefunds = $order->refunds->whereNotIn('status', ['failed', 'canceled']);
                $count = collect($items)->sum(fn (array $item, int $index): int =>
                    max(0, (int) ($item['quantity'] ?? 1) - (int) $reservedRefunds
                        ->where('type', 'item')->where('item_index', $index)->sum('quantity')));
                $hasItemRefunds = $reservedRefunds->isNotEmpty();
                $isPending = $order->status === 'pending';
            @endphp
            <a href="{{ route('warehouse.orders.show', $order->order_number) }}"
               class="group block rounded-3xl bg-white p-4 shadow-sm transition duration-150 hover:shadow-md active:scale-[0.995] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#712B47] sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
                    <span class="font-mono text-xs font-semibold tracking-tight text-zinc-500">{{ $order->order_number }}</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold {{ $isPending ? 'bg-amber-50 text-amber-800' : 'bg-sky-50 text-sky-800' }}">
                        <span class="size-1.5 shrink-0 rounded-full {{ $isPending ? 'bg-amber-500' : 'bg-sky-500' }}"></span>
                        {{ $isPending ? __('To prepare') : __('In progress') }}
                    </span>
                    @if ($hasItemRefunds)
                        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">Partial refund</span>
                    @endif
                </div>

                <div class="mt-3 flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-lg font-extrabold tracking-tight text-zinc-900">{{ trim($order->first_name.' '.($order->last_name ?? '')) }}</h2>
                        <div class="mt-1.5 flex items-start gap-1.5 text-xs leading-relaxed text-zinc-500">
                            <x-lucide-map-pin class="mt-0.5 size-3.5 shrink-0 text-[#AE8A99]" />
                            <span class="min-w-0 line-clamp-2">{{ $order->city }} · {{ $order->shipping_option_name ?: __('Shipping') }}</span>
                        </div>
                    </div>
                    <div class="shrink-0 pt-0.5 text-right text-lg font-extrabold tabular-nums tracking-tight text-[#712B47]">
                        {{ number_format((float) $order->total_amount, 2, ',', ' ') }} €
                    </div>
                </div>

                <div class="mt-4 rounded-2xl bg-[#F9F6F7] p-2.5 sm:p-3">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-x-4">
                        @foreach (array_slice($items, 0, 4) as $itemIndex => $item)
                            @php
                                $packedQuantity = max(0, (int) ($item['quantity'] ?? 1)
                                    - (int) $reservedRefunds->where('type', 'item')
                                        ->where('item_index', $itemIndex)->sum('quantity'));
                            @endphp
                            <div class="flex min-w-0 items-center gap-2.5">
                                @if (filled(data_get($item, 'image')))
                                    <img src="{{ data_get($item, 'image') }}" loading="lazy" alt="" class="size-10 shrink-0 rounded-xl bg-white object-contain">
                                @else
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white"><x-lucide-package class="size-5 text-zinc-400" /></span>
                                @endif
                                <span class="min-w-0 flex-1 truncate text-xs font-medium text-zinc-700" title="{{ data_get($item, 'name', __('Product')) }}">
                                    {{ data_get($item, 'name', __('Product')) }}
                                </span>
                                <span class="shrink-0 rounded-lg bg-white px-2 py-1 text-xs font-extrabold tabular-nums {{ $packedQuantity === 0 ? 'text-rose-700' : 'text-[#712B47]' }}">{{ $packedQuantity === 0 ? 'Refunded' : '×'.$packedQuantity }}</span>
                            </div>
                        @endforeach
                        @if (count($items) > 4)
                            <span class="px-1 text-xs font-semibold text-zinc-500">+{{ count($items) - 4 }} {{ __('more products') }}</span>
                        @endif
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between gap-3">
                    <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-xs font-medium text-zinc-400">
                        <span>{{ $count }} {{ __('items') }}</span>
                        <span aria-hidden="true">·</span>
                        <time datetime="{{ $order->created_at->toIso8601String() }}">{{ $order->created_at->timezone('Europe/Paris')->format('d/m · H:i') }}</time>
                    </div>
                    <span class="flex shrink-0 items-center gap-1.5 text-xs font-bold text-[#712B47]">
                        {{ __('View') }}
                        <x-lucide-arrow-up-right class="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                    </span>
                </div>
            </a>
        @empty
            <div class="rounded-2xl bg-white px-5 py-14 text-center shadow-sm">
                <x-lucide-check-check class="mx-auto size-10 text-emerald-600" />
                <p class="mt-3 font-bold">{{ __('No orders to prepare') }}</p>
                <p class="mt-1 text-sm text-zinc-500">{{ __('Everything is up to date.') }}</p>
            </div>
        @endforelse
    </div>

    @if ($orders->hasPages())
        <div class="mt-5">{{ $orders->links() }}</div>
    @endif

    <div class="fixed inset-x-0 bottom-0 z-20 bg-white/95 px-5 pb-[max(0.9rem,env(safe-area-inset-bottom))] pt-3 shadow-lg backdrop-blur-sm">
        <div class="mx-auto flex max-w-xl items-center justify-between gap-4">
            <div class="flex items-center gap-2 font-semibold text-[#712B47]"><x-lucide-package-check class="size-5" /> {{ __('Orders') }}</div>
            <span class="rounded-full bg-[#F5E9EE] px-3 py-2 text-sm font-bold text-[#712B47]">{{ $counts['all'] }} {{ __('to process') }}</span>
        </div>
    </div>
</x-warehouse.layout>
