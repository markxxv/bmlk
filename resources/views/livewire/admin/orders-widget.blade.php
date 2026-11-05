<div wire:poll.60s class="min-w-0">
    {{-- Maison Plush-style filter pills, with payment states listed first. --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        @foreach ($this->statuses as $key => $label)
            <button
                type="button"
                wire:key="dashboard-order-filter-{{ $key }}"
                wire:click="filterByStatus(@js($key))"
                class="rounded-full px-3 py-1.5 text-xs font-medium transition-colors {{ $statusFilter === $key ? 'bg-gray-950 text-white dark:bg-white dark:text-gray-950' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
            >
                {{ $label }} ({{ $this->statusCounts[$key] ?? 0 }})
            </button>
        @endforeach
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-xs text-gray-500 dark:bg-gray-800/70 dark:text-gray-400">
                    <th class="rounded-l-lg px-3 py-3 text-left font-medium">Order</th>
                    <th class="hidden px-3 py-3 text-left font-medium sm:table-cell">Customer</th>
                    <th class="hidden px-3 py-3 text-left font-medium lg:table-cell">Phone</th>
                    <th class="hidden px-3 py-3 text-left font-medium xl:table-cell">Address</th>
                    <th class="hidden px-3 py-3 text-left font-medium lg:table-cell">Date</th>
                    <th class="px-3 py-3 text-left font-medium">Payment</th>
                    <th class="hidden px-3 py-3 text-left font-medium md:table-cell">Fulfillment</th>
                    <th class="rounded-r-lg px-3 py-3 text-right font-medium">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->orders as $order)
                    <tr wire:key="dashboard-order-{{ $order->id }}" class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800/50">
                        <td class="px-3 py-3">
                            <a
                                href="{{ \App\Filament\Resources\Orders\OrderResource::getUrl('edit', ['record' => $order->id]) }}"
                                class="font-semibold text-gray-950 hover:text-primary-600 dark:text-gray-50 dark:hover:text-primary-400"
                            >{{ $order->order_number }}</a>

                            <div class="mt-1 text-xs text-gray-500 sm:hidden">
                                {{ trim($order->first_name.' '.($order->last_name ?? '')) }}
                                <div class="mt-0.5">{{ $order->city }}, {{ $order->country }}</div>
                                <div class="mt-0.5">{{ $order->created_at->timezone('Europe/Paris')->format('d M Y, H:i') }}</div>
                            </div>
                        </td>

                        <td class="hidden px-3 py-3 font-medium text-gray-800 dark:text-gray-200 sm:table-cell">
                            {{ trim($order->first_name.' '.($order->last_name ?? '')) }}
                        </td>

                        <td class="hidden px-3 py-3 text-gray-600 dark:text-gray-300 lg:table-cell">
                            {{ $order->phone }}
                        </td>

                        <td class="hidden px-3 py-3 xl:table-cell">
                            <div class="text-xs text-gray-700 dark:text-gray-300">{{ $this->formatAddress($order->address) }}</div>
                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $order->city }}, {{ $order->country }}</div>
                        </td>

                        <td class="hidden whitespace-nowrap px-3 py-3 text-xs text-gray-600 dark:text-gray-400 lg:table-cell">
                            {{ $order->created_at->timezone('Europe/Paris')->format('d M Y') }}
                            <div class="mt-0.5 text-gray-400">{{ $order->created_at->timezone('Europe/Paris')->format('H:i') }}</div>
                        </td>

                        <td class="px-3 py-3">
                            <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->paymentBadgeClass($order->payment_status) }}">
                                {{ $this->paymentLabel($order->payment_status) }}
                            </span>
                            @if ($order->payment_status === 'paid' || $order->status === 'cancelled')
                                <div class="mt-1 text-xs text-gray-500 md:hidden">{{ $this->fulfillmentLabel($order->status) }}</div>
                            @endif
                        </td>

                        <td class="hidden px-3 py-3 text-xs text-gray-500 dark:text-gray-400 md:table-cell">
                            @if ($order->payment_status === 'paid' || $order->status === 'cancelled')
                                {{ $this->fulfillmentLabel($order->status) }}
                            @else
                                —
                            @endif
                        </td>

                        <td class="whitespace-nowrap px-3 py-3 text-right font-semibold tabular-nums text-gray-950 dark:text-gray-100">
                            {{ number_format((float) $order->total_amount, 2, ',', ' ') }} €
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                            No orders found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end">
        <a href="{{ \App\Filament\Resources\Orders\OrderResource::getUrl('index') }}" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
            View all orders
        </a>
    </div>
</div>
