@php
    $refundableItems = collect($refundSummary['items'])
        ->filter(fn (array $item): bool => $item['available_quantity'] > 0 && $item['unit_cents'] > 0);
    $remaining = $refundSummary['remaining_cents'];
    $money = fn (int $cents): string => '€'.number_format($cents / 100, 2, '.', ',');
@endphp

<section class="mt-4 rounded-2xl bg-white p-4 shadow-sm" aria-labelledby="warehouse-refund-heading">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 id="warehouse-refund-heading" class="flex items-center gap-2 font-bold">
                <x-lucide-rotate-ccw class="size-5 text-[#712B47]" /> Refunds
            </h2>
            <p class="mt-1 text-sm text-zinc-500">Refunds go back to the customer's original payment method.</p>
        </div>
        @if ($refundSummary['reserved_cents'] > 0)
            <span class="shrink-0 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800">
                {{ $money($refundSummary['reserved_cents']) }} refunded / reserved
            </span>
        @endif
    </div>

    @if ($refundSummary['can_refund'])
        <div class="mt-4 flex items-center justify-between gap-2 rounded-xl bg-zinc-50 px-3 py-3">
            <span class="text-sm text-zinc-600">Available to refund</span>
            <span class="text-lg font-extrabold tabular-nums text-zinc-950">{{ $money($remaining) }}</span>
        </div>
        <button id="open-warehouse-refund" type="button"
            class="mt-3 flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-zinc-950 px-4 py-3 text-sm font-bold text-white active:opacity-80">
            <x-lucide-rotate-ccw class="size-4" /> Issue a refund
        </button>
    @elseif ($refundSummary['has_whole_refund'])
        <p class="mt-3 rounded-xl bg-amber-50 p-3 text-sm font-medium text-amber-800">The full payment has been refunded or reserved. Do not ship this order.</p>
    @elseif ($remaining === 0)
        <p class="mt-3 text-sm font-medium text-emerald-700">The full payment amount has already been refunded or reserved.</p>
    @else
        <p class="mt-3 text-sm text-zinc-500">Refunds are only available for paid orders with a Stripe payment ID.</p>
    @endif

    @if ($refundSummary['history']->isNotEmpty())
        <h3 class="mt-5 text-sm font-bold text-zinc-800">Refund history</h3>
        <div class="mt-2 space-y-2">
            @foreach ($refundSummary['history'] as $refund)
                <div class="flex items-start justify-between gap-3 rounded-xl bg-zinc-50 p-3">
                    <div class="min-w-0">
                        @if ($refund->type === 'items')
                            <p class="text-sm font-semibold">Selected products</p>
                            <ul class="mt-1 space-y-0.5 text-xs text-zinc-600">
                                @foreach (($refund->items ?? []) as $line)
                                    <li>{{ $line['name'] ?? ($refundSummary['items'][$line['item_index'] ?? -1]['name'] ?? 'Product') }}{{ ! empty($line['options']) ? ' · '.$line['options'] : '' }} × {{ $line['quantity'] ?? 0 }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm font-semibold">
                                {{ $refund->type === 'order'
                                    ? 'Remaining order balance'
                                    : (($refundSummary['items'][$refund->item_index]['name'] ?? 'Product').' × '.$refund->quantity) }}
                            </p>
                        @endif
                        <p class="mt-1 text-xs text-zinc-500">{{ $refund->created_at->timezone('Europe/Paris')->format('d M Y · H:i') }}</p>
                        <p class="mt-1 text-xs {{ in_array($refund->status, ['failed', 'canceled']) ? 'text-rose-700' : ($refund->status === 'succeeded' ? 'text-emerald-700' : 'text-amber-700') }}">
                            {{ match ($refund->status) {
                                'succeeded' => 'Succeeded',
                                'failed' => 'Failed',
                                'canceled' => 'Canceled',
                                'needs_review' => 'Check in Stripe',
                                'requires_action' => 'Action required',
                                default => 'Processing',
                            } }}
                        </p>
                        @if (! in_array($refund->status, ['succeeded', 'failed', 'canceled']))
                            <form method="POST" action="{{ route('warehouse.orders.refunds.refresh', [$order->order_number, $refund->id]) }}" class="mt-2">
                                @csrf
                                <button type="submit" class="min-h-11 rounded-lg bg-white px-3 py-2 text-xs font-bold text-[#712B47]">Check status in Stripe</button>
                            </form>
                        @endif
                    </div>
                    <span class="shrink-0 text-sm font-extrabold tabular-nums">{{ $money($refund->amount_cents) }}</span>
                </div>
            @endforeach
        </div>
    @endif
</section>

@if ($refundSummary['can_refund'])
    <dialog id="warehouse-refund-dialog" aria-labelledby="refund-modal-title"
        class="fixed inset-x-0 bottom-0 m-0 max-h-[94dvh] w-full max-w-none overflow-y-auto rounded-t-3xl bg-white p-0 text-zinc-950 shadow-2xl backdrop:bg-zinc-950/65 sm:inset-0 sm:m-auto sm:max-h-[88dvh] sm:max-w-md sm:rounded-3xl">
        <form id="warehouse-refund-form" method="POST" action="{{ route('warehouse.orders.refund', $order->order_number) }}">
            @csrf
            <input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            <input type="hidden" name="confirmation" value="CONFIRM REFUND">

            <div class="sticky top-0 z-20 flex items-center justify-between gap-3 bg-white px-5 py-4 shadow-sm">
                <div>
                    <h2 id="refund-modal-title" class="text-xl font-extrabold">Issue a refund</h2>
                    <p class="mt-0.5 text-xs text-zinc-500">Order {{ $order->order_number }}</p>
                </div>
                <button id="close-warehouse-refund" type="button" aria-label="Close refund dialog"
                    class="flex size-11 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-700">
                    <x-lucide-x class="size-5" />
                </button>
            </div>

            <div id="refund-choose-step" class="pt-4">
                <div class="space-y-4 px-5">
                    <fieldset>
                        <legend class="mb-2 text-sm font-bold">What would you like to refund?</legend>
                        @if ($refundableItems->isNotEmpty())
                            <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl bg-[#F9F1F4] p-3">
                                <input type="radio" name="type" value="items" checked class="size-5 shrink-0 accent-[#712B47]">
                                <span class="min-w-0 flex-1 text-sm font-semibold">Select products</span>
                                <span class="text-xs text-zinc-500">Multiple items</span>
                            </label>
                        @endif
                        <label class="mt-2 flex min-h-14 cursor-pointer items-center gap-3 rounded-xl bg-zinc-100 p-3">
                            <input type="radio" name="type" value="order" {{ $refundableItems->isEmpty() ? 'checked' : '' }}
                                class="size-5 shrink-0 accent-[#712B47]">
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold">Entire remaining balance</span>
                                <span class="block text-xs text-zinc-500">Includes shipping</span>
                            </span>
                            <span class="shrink-0 text-sm font-bold tabular-nums">{{ $money($remaining) }}</span>
                        </label>
                    </fieldset>

                    @if ($refundableItems->isNotEmpty())
                        <fieldset id="refund-product-picker" class="space-y-2">
                            <legend class="mb-2 text-sm font-bold">Choose products and quantities</legend>
                            @foreach ($refundableItems as $item)
                                <div data-refund-row data-index="{{ $item['index'] }}" data-unit="{{ $item['unit_cents'] }}"
                                    data-max="{{ $item['available_quantity'] }}" data-name="{{ $item['name'] }}{{ $item['options'] !== '' ? ' · '.$item['options'] : '' }}"
                                    class="rounded-2xl bg-zinc-100 p-3">
                                    <label class="flex min-h-12 cursor-pointer items-center gap-3">
                                        <input type="checkbox" data-refund-check
                                            aria-label="Refund {{ $item['name'] }}"
                                            class="size-5 shrink-0 accent-[#712B47]">
                                        @if ($item['image'] !== '')
                                            <img src="{{ $item['image'] }}" alt="" loading="lazy" class="size-12 shrink-0 rounded-xl bg-white object-contain">
                                        @else
                                            <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white text-zinc-400"><x-lucide-package class="size-5" /></span>
                                        @endif
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold leading-snug">{{ $item['name'] }}</span>
                                            @if ($item['options'] !== '')
                                                <span class="mt-0.5 block text-xs text-zinc-500">{{ $item['options'] }}</span>
                                            @endif
                                            <span class="mt-1 block text-xs text-zinc-500">{{ $item['available_quantity'] }} available · {{ $money($item['unit_cents']) }} each</span>
                                        </span>
                                    </label>
                                    <div data-refund-controls hidden class="mt-3 rounded-xl bg-white p-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-1">
                                                <button type="button" data-refund-minus aria-label="Decrease refund quantity for {{ $item['name'] }}"
                                                    class="flex size-11 items-center justify-center rounded-lg bg-zinc-100 text-xl font-semibold active:bg-zinc-200">−</button>
                                                <span data-refund-qty class="min-w-9 text-center text-lg font-extrabold tabular-nums">1</span>
                                                <button type="button" data-refund-plus aria-label="Increase refund quantity for {{ $item['name'] }}"
                                                    class="flex size-11 items-center justify-center rounded-lg bg-zinc-100 text-xl font-semibold disabled:opacity-30 active:bg-zinc-200">+</button>
                                            </div>
                                            <span data-refund-line-total class="text-sm font-extrabold tabular-nums text-[#712B47]">{{ $money($item['unit_cents']) }}</span>
                                        </div>
                                    </div>
                                    <input type="hidden" data-refund-index-input name="items[{{ $loop->index }}][item_index]" value="{{ $item['index'] }}" disabled>
                                    <input type="hidden" data-refund-qty-input name="items[{{ $loop->index }}][quantity]" value="1" disabled>
                                </div>
                            @endforeach
                            <p class="pt-1 text-xs text-zinc-500">Only selected products are refunded. Prices include discounts; shipping is excluded.</p>
                        </fieldset>
                    @endif
                </div>

                <div class="sticky bottom-0 z-10 mt-5 bg-white px-5 pb-[max(1rem,env(safe-area-inset-bottom))] pt-4 shadow-[0_-8px_24px_rgba(0,0,0,0.04)]">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <span class="min-w-0 text-sm font-semibold text-zinc-600">
                            <span id="refund-selection-label">0 products selected</span>
                        </span>
                        <span id="refund-preview-amount" class="shrink-0 text-2xl font-extrabold tabular-nums text-[#712B47]">€0.00</span>
                    </div>
                    <button id="refund-review-button" type="button" disabled
                        class="min-h-13 w-full rounded-xl bg-[#712B47] px-4 py-3 text-base font-bold text-white disabled:opacity-40">
                        Review refund
                    </button>
                </div>
            </div>

            <div id="refund-confirm-step" class="space-y-5 px-5 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-4" hidden>
                <div class="rounded-xl bg-rose-50 p-4">
                    <div class="flex items-center gap-2 font-bold text-rose-800">
                        <x-lucide-triangle-alert class="size-5" /> Final confirmation
                    </div>
                    <p class="mt-2 text-sm text-rose-900">Money will be returned to the customer's original payment method. This cannot be undone.</p>
                    @if ($order->tracking_number)
                        <p class="mt-2 text-sm font-semibold text-rose-900">A shipping label already exists. Refunding does not cancel it in Sendcloud.</p>
                    @endif
                </div>
                <div class="rounded-xl bg-zinc-100 p-4">
                    <p class="text-xs font-semibold text-zinc-500">Order {{ $order->order_number }}</p>
                    <p class="mt-3 text-sm font-bold">Refund details</p>
                    <ul id="refund-review-lines" class="mt-2 space-y-2 text-sm text-zinc-700"></ul>
                    <div class="mt-4 flex items-center justify-between gap-3 border-t border-zinc-200 pt-3">
                        <span class="text-sm font-semibold">Amount to refund</span>
                        <span id="refund-review-amount" class="text-2xl font-extrabold tabular-nums text-[#712B47]"></span>
                    </div>
                </div>
                <label class="flex min-h-14 cursor-pointer items-start gap-3 rounded-xl bg-zinc-100 p-4">
                    <input id="refund-confirm-checkbox" type="checkbox" class="mt-0.5 size-5 shrink-0 accent-[#712B47]">
                    <span class="text-sm font-semibold">I have checked each selected product, quantity and the total amount. I confirm this refund.</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <button id="refund-back-button" type="button" class="min-h-13 rounded-xl bg-zinc-100 px-3 py-3 font-semibold">Back</button>
                    <button id="refund-submit-button" type="submit" disabled
                        class="min-h-13 rounded-xl bg-rose-700 px-3 py-3 font-bold text-white disabled:opacity-40">
                        Confirm refund
                    </button>
                </div>
            </div>
        </form>
    </dialog>

    <script>
        (() => {
            const dialog = document.getElementById('warehouse-refund-dialog');
            const form = document.getElementById('warehouse-refund-form');
            const choose = document.getElementById('refund-choose-step');
            const confirmStep = document.getElementById('refund-confirm-step');
            const picker = document.getElementById('refund-product-picker');
            const rows = [...form.querySelectorAll('[data-refund-row]')];
            const preview = document.getElementById('refund-preview-amount');
            const label = document.getElementById('refund-selection-label');
            const review = document.getElementById('refund-review-button');
            const reviewLines = document.getElementById('refund-review-lines');
            const reviewAmount = document.getElementById('refund-review-amount');
            const check = document.getElementById('refund-confirm-checkbox');
            const submit = document.getElementById('refund-submit-button');
            const remaining = {{ $remaining }};
            const money = cents => new Intl.NumberFormat('en-IE', {style: 'currency', currency: 'EUR'}).format(cents / 100);
            const mode = () => form.querySelector('input[name="type"]:checked')?.value;
            let submitting = false;

            const quantities = new Map();
            const selectedLines = () => rows.filter(row => row.querySelector('[data-refund-check]').checked)
                .map(row => ({
                    row,
                    index: Number(row.dataset.index),
                    name: row.dataset.name,
                    qty: quantities.get(row) ?? 1,
                    unit: Number(row.dataset.unit),
                }));

            const update = () => {
                const isItems = mode() === 'items';
                if (picker) picker.hidden = !isItems;
                for (const row of rows) {
                    const chosen = isItems && row.querySelector('[data-refund-check]').checked;
                    const qty = Math.min(Number(row.dataset.max), Math.max(1, quantities.get(row) ?? 1));
                    quantities.set(row, qty);
                    row.querySelector('[data-refund-controls]').hidden = !chosen;
                    row.querySelector('[data-refund-index-input]').disabled = !chosen;
                    const quantityInput = row.querySelector('[data-refund-qty-input]');
                    quantityInput.disabled = !chosen;
                    quantityInput.value = String(qty);
                    row.querySelector('[data-refund-qty]').textContent = String(qty);
                    row.querySelector('[data-refund-line-total]').textContent = money(qty * Number(row.dataset.unit));
                    row.querySelector('[data-refund-plus]').disabled = qty >= Number(row.dataset.max);
                }
                const selected = isItems ? selectedLines() : [];
                const cents = isItems
                    ? selected.reduce((sum, line) => sum + line.qty * line.unit, 0)
                    : mode() === 'order' ? remaining : 0;
                label.textContent = isItems
                    ? selected.length + (selected.length === 1 ? ' product selected' : ' products selected')
                    : 'Entire remaining balance, including shipping';
                preview.textContent = money(cents);
                review.disabled = cents < 1 || cents > remaining || (isItems && selected.length === 0);
                return cents;
            };

            document.getElementById('open-warehouse-refund').addEventListener('click', () => {
                submitting = false;
                form.querySelectorAll('[data-refund-check]').forEach(input => {input.checked = false;});
                form.querySelector('input[name="type"][value="items"]')?.click();
                quantities.clear();
                choose.hidden = false;
                confirmStep.hidden = true;
                check.checked = false;
                submit.disabled = true;
                submit.textContent = 'Confirm refund';
                dialog.showModal();
                update();
            });
            document.getElementById('close-warehouse-refund').addEventListener('click', () => dialog.close());
            dialog.addEventListener('click', event => {
                if (event.target === dialog) dialog.close();
            });
            form.querySelectorAll('input[name="type"]').forEach(input => input.addEventListener('change', update));

            for (const row of rows) {
                row.querySelector('[data-refund-check]').addEventListener('change', () => {
                    if (!quantities.has(row)) quantities.set(row, 1);
                    update();
                });
                row.querySelector('[data-refund-minus]').addEventListener('click', () => {
                    const qty = quantities.get(row) ?? 1;
                    if (qty === 1) {
                        row.querySelector('[data-refund-check]').checked = false;
                    } else {
                        quantities.set(row, qty - 1);
                    }
                    update();
                });
                row.querySelector('[data-refund-plus]').addEventListener('click', () => {
                    quantities.set(row, Math.min(Number(row.dataset.max), (quantities.get(row) ?? 1) + 1));
                    update();
                });
            }

            review.addEventListener('click', () => {
                const cents = update();
                if (review.disabled) return;
                reviewLines.replaceChildren();
                if (mode() === 'order') {
                    const li = document.createElement('li');
                    li.textContent = 'Entire remaining order balance, including shipping';
                    reviewLines.appendChild(li);
                } else {
                    for (const line of selectedLines()) {
                        const li = document.createElement('li');
                        li.className = 'flex items-start justify-between gap-3';
                        const name = document.createElement('span');
                        const amount = document.createElement('span');
                        name.textContent = line.name + ' × ' + line.qty;
                        amount.className = 'shrink-0 font-bold tabular-nums';
                        amount.textContent = money(line.qty * line.unit);
                        li.append(name, amount);
                        reviewLines.appendChild(li);
                    }
                }
                reviewAmount.textContent = money(cents);
                choose.hidden = true;
                confirmStep.hidden = false;
                check.checked = false;
                submit.disabled = true;
                dialog.scrollTop = 0;
            });
            document.getElementById('refund-back-button').addEventListener('click', () => {
                confirmStep.hidden = true;
                choose.hidden = false;
                check.checked = false;
                dialog.scrollTop = 0;
                update();
            });
            check.addEventListener('change', () => {
                submit.disabled = !check.checked || submitting;
            });
            form.addEventListener('submit', event => {
                if (!check.checked || submitting || update() < 1) {
                    event.preventDefault();
                    return;
                }
                submitting = true;
                submit.disabled = true;
                submit.textContent = 'Submitting…';
            });
        })();
    </script>
@endif
