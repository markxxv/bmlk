<x-warehouse.layout title="Orders">
    <header class="mb-5 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="text-[10px] font-extrabold tracking-[0.19em] text-[#712B47]">BLACK MILK · FRANCE</div>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight">{{ __('Orders') }}</h1>
        </div>
        <form method="POST" action="{{ route('warehouse.logout') }}">@csrf
            <button type="submit" aria-label="{{ __('Sign out') }}" class="flex size-11 items-center justify-center rounded-xl bg-white text-zinc-500 shadow-sm">
                <x-lucide-log-out class="size-5" />
            </button>
        </form>
    </header>

    <form method="GET" action="{{ route('warehouse.orders.index') }}" class="mb-4 flex items-center gap-2 rounded-2xl bg-white px-4 shadow-sm">
        @if ($state !== 'all')<input type="hidden" name="state" value="{{ $state }}">@endif
        @if ($sort !== 'newest')<input type="hidden" name="sort" value="{{ $sort }}">@endif
        <x-lucide-search class="size-5 shrink-0 text-zinc-400" />
        <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Order number, customer, city…') }}" class="min-h-12 w-full min-w-0 bg-transparent text-base outline-none placeholder:text-zinc-400" aria-label="{{ __('Search orders') }}">
        @if ($search !== '')
            <a class="text-sm text-[#712B47]" href="{{ route('warehouse.orders.index', ['state' => $state, 'sort' => $sort]) }}">{{ __('Clear') }}</a>
        @endif
    </form>

    @php
        $fulfillmentFilters = [
            'all' => 'All',
            'pending' => 'To prepare',
            'processing' => 'Preparing',
            'shipped' => 'Shipped',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'archived' => 'Archived',
        ];
        $paymentFilters = [
            'paid' => 'Paid',
            'payment_pending' => 'Payment pending',
            'payment_processing' => 'Payment processing',
            'payment_failed' => 'Payment failed',
            'refunds' => 'Refunds',
        ];
    @endphp

    <nav aria-label="{{ __('Filter orders') }}" class="mb-4 space-y-2">
        <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0">
            @foreach ($fulfillmentFilters as $key => $label)
                <a href="{{ route('warehouse.orders.index', array_filter(['state' => $key, 'q' => $search, 'sort' => $sort !== 'newest' ? $sort : null])) }}"
                   @if ($state === $key) aria-current="page" @endif
                   class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold {{ $state === $key ? 'bg-[#712B47] text-white' : 'bg-white text-zinc-600' }}">
                    {{ __($label) }} <span class="{{ $state === $key ? 'text-white' : 'text-zinc-400' }}">{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </div>
        <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0">
            @foreach ($paymentFilters as $key => $label)
                <a href="{{ route('warehouse.orders.index', array_filter(['state' => $key, 'q' => $search, 'sort' => $sort !== 'newest' ? $sort : null])) }}"
                   @if ($state === $key) aria-current="page" @endif
                   class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold {{ $state === $key ? 'bg-[#712B47] text-white' : 'bg-white text-zinc-600' }}">
                    {{ __($label) }} <span class="{{ $state === $key ? 'text-white' : 'text-zinc-400' }}">{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    <div class="mb-3 flex items-center justify-between gap-3 px-1">
        <div class="flex min-w-0 items-center gap-2 text-xs font-semibold text-zinc-700">
            <span class="size-1.5 shrink-0 rounded-full bg-[#712B47]"></span>
            <span>{{ $state === 'all' ? __('All orders') : __($fulfillmentFilters[$state] ?? $paymentFilters[$state] ?? 'Orders') }}</span>
        </div>
        <a href="{{ route('warehouse.orders.index', array_filter(['state' => $state, 'q' => $search, 'sort' => $sort === 'newest' ? 'oldest' : null])) }}"
           class="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-lg px-2 text-xs text-zinc-500 transition hover:bg-white hover:text-[#712B47] focus-visible:outline-2 focus-visible:outline-[#712B47]"
           aria-label="{{ __('Reverse sort order') }}">
            @if ($sort === 'newest')
                <x-lucide-arrow-down-wide-narrow class="size-4" />
                <span>{{ __('Newest first') }}</span>
            @else
                <x-lucide-arrow-down-narrow-wide class="size-4" />
                <span>{{ __('Oldest first') }}</span>
            @endif
        </a>
    </div>

    <div class="space-y-3">
        @forelse ($orders as $order)
            @php
                $items = $order->items ?? [];
                $reservedRefunds = $order->refunds->whereNotIn('status', ['failed', 'canceled']);
                $reservedAmount = (int) $reservedRefunds->sum('amount_cents');
                $totalCents = (int) round((float) $order->total_amount * 100);
                $fullyRefunded = $totalCents > 0 && $reservedAmount >= $totalCents;
                $refundSucceeded = $fullyRefunded && $reservedRefunds->every(fn ($refund) => $refund->status === 'succeeded');
                $reservedQuantities = [];
                foreach ($reservedRefunds as $refund) {
                    if ($refund->type === 'item' && $refund->item_index !== null) {
                        $index = (int) $refund->item_index;
                        $reservedQuantities[$index] = ($reservedQuantities[$index] ?? 0) + (int) $refund->quantity;
                    } elseif ($refund->type === 'items') {
                        foreach (($refund->items ?? []) as $line) {
                            if (! is_array($line) || ! isset($line['item_index'], $line['quantity'])) {
                                continue;
                            }
                            $index = (int) $line['item_index'];
                            $reservedQuantities[$index] = ($reservedQuantities[$index] ?? 0) + (int) $line['quantity'];
                        }
                    }
                }
                $count = collect($items)->sum(fn (array $item, int $index): int =>
                    $fullyRefunded ? 0 : max(0, (int) ($item['quantity'] ?? 1) - ($reservedQuantities[$index] ?? 0)));
                $paymentLabel = match ($order->payment_status) {
                    'paid' => 'Paid',
                    'unpaid' => 'Payment pending',
                    'processing' => 'Payment processing',
                    'failed' => 'Payment failed',
                    default => ucfirst(str_replace('_', ' ', (string) $order->payment_status)),
                };
                $paymentColor = match ($order->payment_status) {
                    'paid' => 'bg-emerald-50 text-emerald-800',
                    'processing' => 'bg-sky-50 text-sky-800',
                    'failed' => 'bg-rose-50 text-rose-800',
                    default => 'bg-amber-50 text-amber-800',
                };
                $statusLabel = match ($order->status) {
                    'pending' => 'To prepare',
                    'processing' => 'Preparing',
                    'shipped' => 'Shipped',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                    'archived' => 'Archived',
                    default => ucfirst(str_replace('_', ' ', (string) $order->status)),
                };
                $statusColor = match ($order->status) {
                    'pending' => 'bg-amber-50 text-amber-800',
                    'processing', 'shipped' => 'bg-sky-50 text-sky-800',
                    'completed' => 'bg-emerald-50 text-emerald-800',
                    'cancelled' => 'bg-rose-50 text-rose-800',
                    default => 'bg-zinc-100 text-zinc-600',
                };
            @endphp
            <a href="{{ route('warehouse.orders.show', $order->order_number) }}"
               class="group block rounded-3xl bg-white p-4 shadow-sm transition duration-150 hover:shadow-md active:scale-[0.995] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#712B47] sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
                    <span class="font-mono text-xs font-semibold tracking-tight text-zinc-500">{{ $order->order_number }}</span>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $paymentColor }}">{{ __($paymentLabel) }}</span>
                        @if ($order->payment_status === 'paid')
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusColor }}">{{ __($statusLabel) }}</span>
                        @endif
                        @if ($fullyRefunded)
                            <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">{{ $refundSucceeded ? __('Refunded') : __('Refund pending') }}</span>
                        @elseif ($reservedAmount > 0)
                            <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">{{ __('Partial refund') }}</span>
                        @endif
                    </div>
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
                                $packedQuantity = $fullyRefunded ? 0 : max(0, (int) ($item['quantity'] ?? 1)
                                    - ($reservedQuantities[$itemIndex] ?? 0));
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
                                <span class="shrink-0 rounded-lg bg-white px-2 py-1 text-xs font-extrabold tabular-nums {{ $packedQuantity === 0 ? 'text-rose-700' : 'text-[#712B47]' }}">{{ $packedQuantity === 0 && $reservedAmount > 0 ? __('Refunded') : '×'.$packedQuantity }}</span>
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
                <p class="mt-3 font-bold">{{ __('No orders found') }}</p>
                <p class="mt-1 text-sm text-zinc-500">{{ __('Try another filter or search.') }}</p>
            </div>
        @endforelse
    </div>

    @if ($orders->hasPages())
        <div class="mt-5">{{ $orders->links() }}</div>
    @endif

    <div class="fixed inset-x-0 bottom-0 z-20 bg-white/95 px-5 pb-[max(0.9rem,env(safe-area-inset-bottom))] pt-3 shadow-lg backdrop-blur-sm">
        <div class="mx-auto flex max-w-xl items-center justify-between gap-4">
            <div class="flex items-center gap-2 font-semibold text-[#712B47]"><x-lucide-package-check class="size-5" /> {{ __('Orders') }}</div>
            <span class="rounded-full bg-[#F5E9EE] px-3 py-2 text-sm font-bold text-[#712B47]">{{ $counts['pending'] }} {{ __('to prepare') }}</span>
        </div>
    </div>
</x-warehouse.layout>
