<x-warehouse.layout :title="$order->order_number">
    <header class="mb-4 flex items-start gap-3">
        <a href="{{ route('warehouse.orders.index') }}" aria-label="{{ __('Back to orders') }}" class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white text-[#712B47] shadow-sm">
            <x-lucide-arrow-left class="size-5" />
        </a>
        <div class="min-w-0 flex-1">
            <div class="text-[10px] font-extrabold tracking-[0.18em] text-[#712B47]">BLACK MILK · FRANCE</div>
            <h1 class="mt-1 break-all text-lg font-extrabold tracking-tight">{{ $order->order_number }}</h1>
            <div class="mt-2 flex flex-wrap gap-1.5">
                @if ($refundSummary['has_whole_refund'])
                    <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700">
                        {{ $refundSummary['history']->whereNotIn('status', ['failed', 'canceled'])->every(fn ($refund) => $refund->status === 'succeeded') ? 'Refunded' : 'Refund pending' }}
                    </span>
                @elseif ($refundSummary['reserved_cents'] > 0)
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800">Partially refunded</span>
                @endif
                @if ($order->payment_status === 'paid')
                    @unless ($refundSummary['has_whole_refund'])
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{{ __('Paid') }}</span>
                    @endunless
                    <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-800">{{ $statusLabel }}</span>
                @else
                    @php
                        $paymentLabel = match ($order->payment_status) {
                            'unpaid' => 'Payment pending',
                            'processing' => 'Payment processing',
                            'failed' => 'Payment failed',
                            default => 'Payment pending',
                        };
                    @endphp
                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $order->payment_status === 'failed' ? 'bg-rose-50 text-rose-800' : 'bg-amber-50 text-amber-800' }}">{{ __($paymentLabel) }}</span>
                @endif
            </div>
        </div>
    </header>

    @if (session('success'))
        <div role="status" class="mb-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="mb-3 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ $errors->first() }}</div>
    @endif

    <div class="mb-4 flex items-center justify-between gap-3 rounded-2xl bg-white px-4 py-3 shadow-sm">
        <div class="min-w-0">
            <div class="truncate text-sm font-bold">{{ trim($order->first_name.' '.($order->last_name ?? '')) }}</div>
            <div class="mt-1 truncate text-xs text-zinc-500">{{ $order->city }} · {{ $order->shipping_option_name ?: __('Shipping') }}</div>
        </div>
        <div class="shrink-0 text-right">
            <div class="text-sm font-extrabold text-[#712B47]">{{ number_format((float) $order->total_amount, 2, ',', ' ') }} €</div>
            <div class="text-xs text-zinc-400">{{ ($order->paid_at ?? $order->created_at)->timezone('Europe/Paris')->format('d/m · H:i') }}</div>
        </div>
    </div>

    @if (filled($order->tracking_number))
        <section aria-label="{{ __('Tracking number') }}" class="mb-4 rounded-2xl bg-white p-4 shadow-sm">
            <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-zinc-500">
                <x-lucide-truck class="size-4 text-[#712B47]" />
                {{ __('Tracking number') }}
            </div>
            <div class="flex items-center gap-3">
                <span id="warehouse-tracking-number" class="min-w-0 flex-1 break-all font-mono text-base font-bold tracking-tight text-zinc-950">{{ $order->tracking_number }}</span>
                <button id="warehouse-tracking-copy" type="button" aria-label="{{ __('Copy tracking number') }}"
                    class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-[#F9EAF0] px-3 text-sm font-bold text-[#712B47] transition-colors active:opacity-80">
                    <x-lucide-copy id="warehouse-tracking-copy-icon" class="size-4" />
                    <x-lucide-check id="warehouse-tracking-check-icon" class="hidden size-4" />
                    <span id="warehouse-tracking-copy-label" aria-live="polite">{{ __('Copy') }}</span>
                </button>
            </div>
        </section>
    @endif

    <section aria-labelledby="warehouse-items-heading" class="overflow-hidden rounded-2xl bg-white shadow-sm">
        <div class="flex items-center justify-between bg-[#F9EAF0] px-4 py-3.5">
            <h2 id="warehouse-items-heading" class="flex items-center gap-2 text-lg font-extrabold"><x-lucide-package class="size-5 text-[#712B47]" /> {{ __('Items') }}</h2>
            <span class="rounded-full bg-white px-3 py-1 text-sm font-bold text-[#712B47]">{{ collect($refundSummary['items'])->sum('available_quantity') }} {{ $order->payment_status === 'paid' ? __('to pack') : __('items') }}</span>
        </div>

        <div class="divide-y divide-zinc-100 px-4">
            @forelse (($order->items ?? []) as $index => $item)
                @php
                    $refundItem = $refundSummary['items'][$index] ?? null;
                    $packingQuantity = $refundItem['available_quantity'] ?? (int) data_get($item, 'quantity', 1);
                    $refundedQuantity = $refundItem['refunded_quantity'] ?? 0;
                @endphp
                <div class="flex items-start gap-3 py-4">
                    @if (filled(data_get($item, 'image')))
                        <img src="{{ data_get($item, 'image') }}" alt="" class="size-20 shrink-0 rounded-xl bg-zinc-100 object-contain" loading="eager">
                    @else
                        <div class="flex size-20 shrink-0 items-center justify-center rounded-xl bg-zinc-100"><x-lucide-package class="size-7 text-zinc-400" /></div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-bold leading-tight">{{ data_get($item, 'name', __('Product')) }}</div>
                        @foreach ((data_get($item, 'options') ?? []) as $option)
                            @if (filled(data_get($option, 'display')))
                                <div class="mt-1 text-xs text-zinc-500">{{ data_get($option, 'label') }}{{ filled(data_get($option, 'label')) ? ' · ' : '' }}{{ data_get($option, 'display') }}</div>
                            @endif
                        @endforeach
                        <div class="mt-2 text-xs text-zinc-500">{{ number_format((float) data_get($item, 'unit_price', 0), 2, ',', ' ') }} € / {{ __('unit') }}</div>
                        @if ($refundedQuantity > 0)
                            <p class="mt-1 text-xs font-bold text-rose-700">{{ $refundedQuantity }} {{ __('refunded / reserved') }}</p>
                        @endif
                    </div>
                    <div class="shrink-0 rounded-xl bg-[#F9EAF0] px-3 py-2 text-center">
                        <div class="text-[10px] font-semibold text-[#712B47]">{{ __('QTY') }}</div>
                        <div class="text-2xl font-black leading-tight text-[#712B47]">×{{ $packingQuantity }}</div>
                        @if ($refundedQuantity > 0 || $refundSummary['has_whole_refund'])
                            <p class="mt-1 text-[10px] font-semibold text-rose-700">{{ __('To pack') }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="py-5 text-sm text-zinc-500">{{ __('No items.') }}</p>
            @endforelse
        </div>
    </section>

    @if ($order->payment_status === 'paid' && ! $refundSummary['has_whole_refund'])
    <section aria-label="{{ __('Shipping label') }}" class="mt-4">
        @if ($refundSummary['reserved_cents'] > 0)
            <p class="mb-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">Some items have been refunded. Verify the parcel contents and weight before purchasing a shipping label.</p>
        @endif
        @if ($labelReady)
            <a href="{{ route('warehouse.orders.label', $order->order_number) }}" class="flex min-h-14 items-center justify-center gap-3 rounded-2xl bg-[#712B47] px-4 py-4 text-center text-sm font-bold text-white shadow-sm">
                <x-lucide-printer class="size-5 shrink-0" /> {{ __('Open / print PDF label') }}
            </a>
            <p class="mt-2 text-center text-xs text-zinc-500">{{ __('Tracking: :tracking', ['tracking' => $order->tracking_number]) }}</p>
        @elseif ($order->tracking_number)
            <p class="rounded-xl bg-amber-50 p-4 text-sm font-medium text-amber-800">{{ __('Label created, but the PDF is unavailable. Check Sendcloud.') }}</p>
        @elseif (in_array($order->status, ['pending', 'processing'], true) && is_array($order->shipping_parcel))
            <form method="POST" action="{{ route('warehouse.orders.create-label', $order->order_number) }}" onsubmit="return confirm(@js(__('Create the Sendcloud shipping label? Shipping charges may apply.')));">
                @csrf
                <button type="submit" class="flex min-h-14 w-full items-center justify-center gap-3 rounded-2xl bg-[#712B47] px-4 py-4 text-center text-sm font-bold text-white shadow-sm active:opacity-80">
                    <x-lucide-printer class="size-5 shrink-0" /> {{ __('Create Sendcloud shipping label') }}
                </button>
            </form>
        @else
            <p class="rounded-xl bg-zinc-100 p-4 text-sm text-zinc-600">{{ __('Label unavailable for this status or incomplete parcel details.') }}</p>
        @endif
    </section>

    <section aria-labelledby="warehouse-status-heading" class="mt-4 rounded-2xl bg-white px-4 py-4 shadow-sm">
        <h2 id="warehouse-status-heading" class="mb-3 flex items-center gap-2 font-bold"><x-lucide-list-check class="size-5 text-[#712B47]" />{{ __('Status') }}</h2>
        <div class="flex flex-wrap gap-2">
            @if ($order->status === 'pending')
                <form method="POST" action="{{ route('warehouse.orders.status', $order->order_number) }}">@csrf
                    <input type="hidden" name="status" value="processing">
                    <button class="min-h-11 rounded-xl bg-[#F9EAF0] px-4 py-2.5 text-sm font-bold text-[#712B47]">{{ __('Start preparing') }}</button>
                </form>
            @elseif ($order->status === 'processing')
                <span class="inline-flex min-h-11 items-center rounded-xl bg-sky-50 px-4 text-sm font-bold text-sky-700">{{ __('Preparing') }}</span>
                <form method="POST" action="{{ route('warehouse.orders.status', $order->order_number) }}">@csrf
                    <input type="hidden" name="status" value="pending">
                    <button class="min-h-11 rounded-xl bg-zinc-100 px-4 py-2.5 text-sm text-zinc-700">{{ __('Move back to pending') }}</button>
                </form>
            @elseif ($order->status === 'shipped')
                <form method="POST" action="{{ route('warehouse.orders.status', $order->order_number) }}">@csrf
                    <input type="hidden" name="status" value="completed">
                    <button class="min-h-11 rounded-xl bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700">{{ __('Mark completed') }}</button>
                </form>
            @else
                <span class="rounded-xl bg-zinc-100 px-4 py-2.5 text-sm font-semibold text-zinc-600">{{ $statusLabel }}</span>
            @endif
            @if (in_array($order->status, ['shipped', 'completed'], true))
                <form method="POST" action="{{ route('warehouse.orders.status', $order->order_number) }}" onsubmit="return confirm(@js(__('Archive this order?')));">@csrf
                    <input type="hidden" name="status" value="archived">
                    <button class="min-h-11 rounded-xl bg-zinc-100 px-4 py-2.5 text-sm text-zinc-500">{{ __('Archive') }}</button>
                </form>
            @endif
        </div>
    </section>

    @elseif ($refundSummary['has_whole_refund'])
        <p class="mt-4 rounded-xl bg-rose-50 p-4 text-sm font-bold text-rose-800">Full payment refunded or reserved. Shipment and fulfillment actions are disabled.</p>
    @else
        <p class="mt-4 rounded-xl bg-amber-50 p-4 text-sm font-semibold text-amber-900">Payment has not been confirmed. This order is read-only until payment succeeds.</p>
    @endif

    <section aria-labelledby="warehouse-shipping-heading" class="mt-4 rounded-2xl bg-white p-4 shadow-sm">
        <h2 id="warehouse-shipping-heading" class="mb-3 flex items-center gap-2 font-bold"><x-lucide-map-pin class="size-5 text-[#712B47]" />{{ __('Shipping') }}</h2>
        <div class="text-sm font-semibold">{{ trim($order->first_name.' '.($order->last_name ?? '')) }}</div>
        <address class="mt-1 text-sm not-italic leading-relaxed text-zinc-600">
            {{ $order->address }}<br>
            {{ $order->zip }} {{ $order->city }}<br>
            {{ $order->country }}
        </address>
        @if ($order->service_point_id)
            <div class="mt-3 rounded-xl bg-zinc-100 p-3 text-sm">
                <div class="text-xs font-semibold text-zinc-500">{{ __('Pickup point') }}</div>
                <div class="mt-1 font-semibold">{{ $order->service_point_name }}</div>
                <div class="text-zinc-600">{{ $order->service_point_address }}</div>
            </div>
        @endif
        <div class="mt-4 flex flex-wrap gap-3 text-sm">
            @if ($order->phone)
                <a class="font-medium text-[#712B47] underline" href="tel:{{ preg_replace('/[^+0-9]/', '', $order->phone) }}">{{ $order->phone }}</a>
            @endif
            @if ($order->email)
                <a class="min-w-0 break-all text-[#712B47] underline" href="mailto:{{ $order->email }}">{{ $order->email }}</a>
            @endif
        </div>
        @if (filled($order->message))
            <div class="mt-4 rounded-xl bg-zinc-100 p-3 text-sm"><div class="mb-1 text-xs font-semibold text-zinc-500">{{ __('Customer note') }}</div>{{ $order->message }}</div>
        @endif
    </section>

    <section aria-label="{{ __('Summary') }}" class="mt-4 rounded-2xl bg-white p-4 text-sm shadow-sm">
        <div class="flex justify-between gap-3 py-1 text-zinc-600"><span>{{ __('Subtotal') }}</span><span>{{ number_format((float) $order->subtotal, 2, ',', ' ') }} €</span></div>
        @if ((float) $order->discount_amount > 0)
            <div class="flex justify-between gap-3 py-1 text-zinc-600"><span>{{ __('Discount') }}</span><span>−{{ number_format((float) $order->discount_amount, 2, ',', ' ') }} €</span></div>
        @endif
        <div class="flex justify-between gap-3 py-1 text-zinc-600"><span>{{ __('Shipping') }}</span><span>{{ number_format((float) $order->delivery_cost, 2, ',', ' ') }} €</span></div>
        <div class="mt-2 flex justify-between gap-3 text-base font-extrabold text-[#712B47]"><span>{{ __('Total paid') }}</span><span>{{ number_format((float) $order->total_amount, 2, ',', ' ') }} €</span></div>
    </section>

    @if ($order->payment_status === 'paid')
        @include('warehouse.refunds')
    @endif

    @php
        // Only expose actual HTTPS tracking URLs returned by Sendcloud.
        $trackingUrl = filled($order->tracking_number)
            && is_string($order->tracking_url)
            && filter_var($order->tracking_url, FILTER_VALIDATE_URL)
            && parse_url($order->tracking_url, PHP_URL_SCHEME) === 'https'
                ? $order->tracking_url : null;
    @endphp
    @if ($trackingUrl)
        <a href="{{ $trackingUrl }}" target="_blank" rel="noopener noreferrer"
            class="mt-4 flex min-h-14 items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-bold text-[#712B47] shadow-sm active:opacity-80">
            <x-lucide-map-pinned class="size-5" />
            {{ __('Track shipment') }}
            <x-lucide-arrow-up-right class="size-4" />
        </a>
    @endif

    @if (filled($order->tracking_number))
        <script>
            (() => {
                const button = document.getElementById('warehouse-tracking-copy');
                const copyIcon = document.getElementById('warehouse-tracking-copy-icon');
                const checkIcon = document.getElementById('warehouse-tracking-check-icon');
                const label = document.getElementById('warehouse-tracking-copy-label');
                const number = document.getElementById('warehouse-tracking-number').textContent.trim();
                let resetTimer;

                button.addEventListener('click', async () => {
                    let copied = false;
                    try {
                        if (navigator.clipboard?.writeText) {
                            await navigator.clipboard.writeText(number);
                            copied = true;
                        } else {
                            const input = document.createElement('textarea');
                            input.value = number;
                            input.setAttribute('readonly', '');
                            input.style.position = 'fixed';
                            input.style.opacity = '0';
                            document.body.appendChild(input);
                            input.select();
                            copied = document.execCommand('copy');
                            input.remove();
                        }
                    } catch {
                        copied = false;
                    }

                    clearTimeout(resetTimer);
                    copyIcon.classList.toggle('hidden', copied);
                    checkIcon.classList.toggle('hidden', !copied);
                    button.classList.toggle('bg-emerald-50', copied);
                    button.classList.toggle('text-emerald-700', copied);
                    button.classList.toggle('bg-[#F9EAF0]', !copied);
                    button.classList.toggle('text-[#712B47]', !copied);
                    label.textContent = copied ? 'Copied' : 'Copy failed';

                    resetTimer = setTimeout(() => {
                        copyIcon.classList.remove('hidden');
                        checkIcon.classList.add('hidden');
                        button.classList.remove('bg-emerald-50', 'text-emerald-700');
                        button.classList.add('bg-[#F9EAF0]', 'text-[#712B47]');
                        label.textContent = 'Copy';
                    }, 2200);
                });
            })();
        </script>
    @endif

    <nav aria-label="{{ __('Order navigation') }}" class="fixed inset-x-0 bottom-0 z-20 bg-white/95 px-5 pb-[max(0.9rem,env(safe-area-inset-bottom))] pt-3 shadow-lg backdrop-blur-sm">
        <div class="mx-auto flex max-w-xl items-center justify-between gap-3">
            <a href="{{ route('warehouse.orders.index') }}" class="flex min-h-11 items-center gap-2 rounded-xl bg-zinc-100 px-4 text-sm font-bold text-zinc-700"><x-lucide-arrow-left class="size-4" />{{ __('Orders') }}</a>
            <span class="text-sm font-semibold text-[#712B47]">{{ $statusLabel }}</span>
        </div>
    </nav>
</x-warehouse.layout>
