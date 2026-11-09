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
        <p class="mt-3 rounded-xl bg-amber-50 p-3 text-sm font-medium text-amber-800">A full refund has been requested. Do not ship this order.</p>
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
                        <p class="text-sm font-semibold">
                            {{ $refund->type === 'order'
                                ? 'Remaining order balance'
                                : (($refundSummary['items'][$refund->item_index]['name'] ?? 'Product').' × '.$refund->quantity) }}
                        </p>
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
                    </div>
                    <span class="shrink-0 text-sm font-extrabold tabular-nums">{{ $money($refund->amount_cents) }}</span>
                </div>
            @endforeach
        </div>
    @endif
</section>

@if ($refundSummary['can_refund'])
    <dialog id="warehouse-refund-dialog" aria-labelledby="refund-modal-title"
        class="fixed inset-x-0 bottom-0 m-0 max-h-[92dvh] w-full max-w-none overflow-y-auto rounded-t-3xl bg-white p-0 text-zinc-950 shadow-2xl backdrop:bg-zinc-950/65 sm:inset-0 sm:m-auto sm:max-h-[88dvh] sm:max-w-md sm:rounded-3xl">
        <form id="warehouse-refund-form" method="POST" action="{{ route('warehouse.orders.refund', $order->order_number) }}">
            @csrf
            <input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            <input type="hidden" name="confirmation" value="CONFIRM REFUND">
            <div class="sticky top-0 z-10 flex items-center justify-between gap-3 bg-white px-5 py-4 shadow-sm">
                <h2 id="refund-modal-title" class="text-xl font-extrabold">Issue a refund</h2>
                <button id="close-warehouse-refund" type="button" aria-label="Close refund dialog"
                    class="flex size-11 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-700">
                    <x-lucide-x class="size-5" />
                </button>
            </div>

            <div id="refund-choose-step" class="space-y-5 px-5 pb-5 pt-4">
                <div>
                    <p class="text-sm font-semibold text-zinc-700">Choose what to refund</p>
                    <label class="mt-3 flex min-h-16 cursor-pointer items-center gap-3 rounded-xl bg-[#F9F1F4] p-4">
                        <input type="radio" name="type" value="order" checked class="size-5 shrink-0 accent-[#712B47]">
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-bold">Remaining order balance</span>
                            <span class="block text-xs text-zinc-600">Includes shipping and any amount not yet refunded.</span>
                        </span>
                        <span class="shrink-0 text-sm font-extrabold tabular-nums">{{ $money($remaining) }}</span>
                    </label>
                    @if ($refundableItems->isNotEmpty())
                        <label class="mt-2 flex min-h-14 cursor-pointer items-center gap-3 rounded-xl bg-zinc-100 p-4">
                            <input type="radio" name="type" value="item" class="size-5 accent-[#712B47]">
                            <span class="text-sm font-bold">One product / selected quantity</span>
                        </label>
                    @endif
                </div>

                <fieldset id="refund-product-picker" class="space-y-2" hidden>
                    <legend class="mb-2 text-sm font-semibold">Choose a product</legend>
                    @foreach ($refundableItems as $item)
                        <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl bg-zinc-100 p-3">
                            <input type="radio" name="item_index" value="{{ $item['index'] }}" data-refund-item
                                data-unit="{{ $item['unit_cents'] }}" data-max="{{ $item['available_quantity'] }}"
                                data-name="{{ $item['name'] }}" class="size-5 shrink-0 accent-[#712B47]">
                            <span class="min-w-0 flex-1 text-sm font-semibold">{{ $item['name'] }}</span>
                            <span class="shrink-0 text-right text-xs text-zinc-600">{{ $item['available_quantity'] }} left
                                <span class="block font-bold text-zinc-900">{{ $money($item['unit_cents']) }} each</span>
                            </span>
                        </label>
                    @endforeach

                    <div class="mt-3 flex items-center justify-between gap-2 rounded-xl bg-[#F9F1F4] px-3 py-3">
                        <span class="text-sm font-semibold">Quantity to refund</span>
                        <div class="flex items-center gap-3">
                            <button id="refund-quantity-minus" type="button" aria-label="Decrease refund quantity"
                                class="flex size-11 items-center justify-center rounded-xl bg-white text-xl font-bold">−</button>
                            <span id="refund-quantity-display" class="min-w-5 text-center text-lg font-extrabold tabular-nums">1</span>
                            <button id="refund-quantity-plus" type="button" aria-label="Increase refund quantity"
                                class="flex size-11 items-center justify-center rounded-xl bg-white text-xl font-bold">+</button>
                        </div>
                        <input type="hidden" id="refund-quantity" name="quantity" value="1">
                    </div>
                    <p class="text-xs text-zinc-500">Product refunds use the amount paid after discounts. Shipping is not refunded.</p>
                </fieldset>

                <div class="flex items-center justify-between gap-2 rounded-xl bg-zinc-950 p-4 text-white">
                    <span class="text-sm font-semibold">Refund amount</span>
                    <span id="refund-preview-amount" class="text-2xl font-extrabold tabular-nums">{{ $money($remaining) }}</span>
                </div>
                <button id="refund-review-button" type="button"
                    class="min-h-13 w-full rounded-xl bg-[#712B47] px-4 py-3 text-base font-bold text-white disabled:opacity-40">
                    Review refund
                </button>
            </div>

            <div id="refund-confirm-step" class="space-y-5 px-5 pb-6 pt-4" hidden>
                <div class="rounded-xl bg-rose-50 p-4">
                    <div class="flex items-center gap-2 font-bold text-rose-800">
                        <x-lucide-alert-triangle class="size-5" /> Final confirmation
                    </div>
                    <p class="mt-2 text-sm text-rose-900">This action sends money back to the customer and cannot be undone.</p>
                </div>
                <div class="rounded-xl bg-zinc-100 p-4">
                    <p class="text-xs font-semibold text-zinc-500">Order {{ $order->order_number }}</p>
                    <p id="refund-review-description" class="mt-3 text-sm font-bold"></p>
                    <p class="mt-3 text-sm text-zinc-600">Amount to refund</p>
                    <p id="refund-review-amount" class="mt-1 text-3xl font-extrabold tabular-nums text-[#712B47]"></p>
                    <p class="mt-2 text-xs text-zinc-600">Refunded to the original Stripe payment method.</p>
                </div>
                <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-zinc-100 p-4">
                    <input id="refund-confirm-checkbox" type="checkbox" class="mt-0.5 size-5 shrink-0 accent-[#712B47]">
                    <span class="text-sm font-semibold">I have checked the order, items and exact amount. I confirm this refund.</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <button id="refund-back-button" type="button"
                        class="min-h-13 rounded-xl bg-zinc-100 px-3 py-3 font-semibold">Back</button>
                    <button id="refund-submit-button" type="submit" disabled
                        class="min-h-13 rounded-xl bg-rose-700 px-3 py-3 font-bold text-white disabled:opacity-40">
                        Confirm refund
                    </button>
                </div>
            </div>
            <div class="h-[env(safe-area-inset-bottom)] bg-white"></div>
        </form>
    </dialog>

    <script>
        (() => {
            const dialog = document.getElementById('warehouse-refund-dialog');
            const form = document.getElementById('warehouse-refund-form');
            const choose = document.getElementById('refund-choose-step');
            const confirmStep = document.getElementById('refund-confirm-step');
            const picker = document.getElementById('refund-product-picker');
            const amountPreview = document.getElementById('refund-preview-amount');
            const reviewAmount = document.getElementById('refund-review-amount');
            const reviewDescription = document.getElementById('refund-review-description');
            const quantityDisplay = document.getElementById('refund-quantity-display');
            const quantityInput = document.getElementById('refund-quantity');
            const check = document.getElementById('refund-confirm-checkbox');
            const submit = document.getElementById('refund-submit-button');
            const items = [...form.querySelectorAll('[data-refund-item]')];
            const remaining = {{ $remaining }};
            const money = cents => new Intl.NumberFormat('en-IE', {style: 'currency', currency: 'EUR'}).format(cents / 100);
            let qty = 1;
            let submitting = false;
            const mode = () => form.querySelector('input[name="type"]:checked').value;
            const selected = () => items.find(item => item.checked);

            const update = () => {
                const isItem = mode() === 'item';
                picker.hidden = !isItem;
                const item = selected();
                if (isItem && item) qty = Math.min(Math.max(1, qty), Number(item.dataset.max));
                quantityInput.value = String(qty);
                quantityDisplay.textContent = String(qty);
                document.getElementById('refund-quantity-minus').disabled = qty <= 1;
                document.getElementById('refund-quantity-plus').disabled = !item || qty >= Number(item.dataset.max);
                const cents = isItem ? (item ? Number(item.dataset.unit) * qty : 0) : remaining;
                amountPreview.textContent = money(cents);
                document.getElementById('refund-review-button').disabled = cents < 1 || cents > remaining;
                return cents;
            };

            document.getElementById('open-warehouse-refund').addEventListener('click', () => {
                check.checked = false;
                submit.disabled = true;
                choose.hidden = false;
                confirmStep.hidden = true;
                dialog.showModal();
                update();
            });
            document.getElementById('close-warehouse-refund').addEventListener('click', () => dialog.close());
            dialog.addEventListener('click', event => {
                if (event.target === dialog) dialog.close();
            });
            form.querySelectorAll('input[name="type"]').forEach(input => input.addEventListener('change', () => {
                if (mode() === 'item' && !selected() && items.length) items[0].checked = true;
                qty = 1;
                update();
            }));
            items.forEach(item => item.addEventListener('change', () => {qty = 1; update();}));
            document.getElementById('refund-quantity-minus').addEventListener('click', () => {qty = Math.max(1, qty - 1); update();});
            document.getElementById('refund-quantity-plus').addEventListener('click', () => {qty += 1; update();});
            document.getElementById('refund-review-button').addEventListener('click', () => {
                const cents = update();
                if (cents < 1 || cents > remaining) return;
                reviewAmount.textContent = money(cents);
                reviewDescription.textContent = mode() === 'order'
                    ? 'Refund all remaining order funds (including shipping)'
                    : selected().dataset.name + ' × ' + qty;
                choose.hidden = true;
                confirmStep.hidden = false;
                check.checked = false;
                submit.disabled = true;
            });
            document.getElementById('refund-back-button').addEventListener('click', () => {
                confirmStep.hidden = true;
                choose.hidden = false;
                check.checked = false;
                update();
            });
            check.addEventListener('change', () => {submit.disabled = !check.checked || submitting;});
            form.addEventListener('submit', event => {
                if (!check.checked || submitting) {event.preventDefault(); return;}
                submitting = true;
                submit.disabled = true;
                submit.textContent = 'Submitting…';
            });
        })();
    </script>
@endif
