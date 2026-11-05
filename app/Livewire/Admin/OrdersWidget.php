<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class OrdersWidget extends Component
{
    // Maison Plush-style pills, but payment comes before fulfillment.
    private const FILTERS = [
        'all' => 'All orders',
        'unpaid' => 'Payment pending',
        'paid' => 'Paid',
        'payment_processing' => 'Payment processing',
        'payment_failed' => 'Payment failed',
        'to_prepare' => 'To prepare',
        'preparing' => 'Preparing',
        'shipped' => 'Shipped',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    #[Url(as: 'orders')]
    public string $statusFilter = 'all';

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);

        if (! array_key_exists($this->statusFilter, self::FILTERS)) {
            $this->statusFilter = 'all';
        }
    }

    public function filterByStatus(string $status): void
    {
        if (array_key_exists($status, self::FILTERS)) {
            $this->statusFilter = $status;
        }
    }

    #[Computed]
    public function statuses(): array
    {
        return self::FILTERS;
    }

    #[Computed]
    public function orders(): Collection
    {
        $query = Order::query()
            ->select([
                'id', 'order_number', 'first_name', 'last_name', 'phone',
                'address', 'city', 'country', 'created_at',
                'payment_status', 'status', 'total_amount',
            ]);

        $this->applyFilter($query);

        return $query->latest('created_at')
            ->latest('id')
            ->limit(60)
            ->get();
    }

    #[Computed]
    public function statusCounts(): array
    {
        // One grouped DB query for all filter counters.
        $rows = Order::query()
            ->select(['payment_status', 'status'])
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('payment_status', 'status')
            ->get();

        $counts = array_fill_keys(array_keys(self::FILTERS), 0);

        foreach ($rows as $row) {
            $count = (int) $row->aggregate;

            $counts['all'] += $count;

            $paymentKey = match ($row->payment_status) {
                'unpaid' => 'unpaid',
                'paid' => 'paid',
                'processing' => 'payment_processing',
                'failed' => 'payment_failed',
                default => null,
            };

            if ($paymentKey !== null) {
                $counts[$paymentKey] += $count;
            }

            if ($row->payment_status === 'paid') {
                $fulfillmentKey = match ($row->status) {
                    'pending' => 'to_prepare',
                    'processing' => 'preparing',
                    'shipped' => 'shipped',
                    'completed' => 'completed',
                    default => null,
                };

                if ($fulfillmentKey !== null) {
                    $counts[$fulfillmentKey] += $count;
                }
            }

            if ($row->status === 'cancelled') {
                $counts['cancelled'] += $count;
            }
        }

        return $counts;
    }

    private function applyFilter(Builder $query): void
    {
        switch ($this->statusFilter) {
            case 'unpaid':
                $query->where('payment_status', 'unpaid');
                break;
            case 'paid':
                $query->where('payment_status', 'paid');
                break;
            case 'payment_processing':
                $query->where('payment_status', 'processing');
                break;
            case 'payment_failed':
                $query->where('payment_status', 'failed');
                break;
            case 'to_prepare':
            case 'preparing':
            case 'shipped':
            case 'completed':
                $status = match ($this->statusFilter) {
                    'to_prepare' => 'pending',
                    'preparing' => 'processing',
                    default => $this->statusFilter,
                };

                $query->where('payment_status', 'paid')
                    ->where('status', $status);
                break;
            case 'cancelled':
                $query->where('status', 'cancelled');
                break;
        }
    }

    public function paymentLabel(?string $status): string
    {
        return match ($status) {
            'paid' => 'Paid',
            'unpaid' => 'Payment pending',
            'processing' => 'Processing',
            'failed' => 'Failed',
            default => Str::headline((string) ($status ?? 'Unknown')),
        };
    }

    public function paymentBadgeClass(?string $status): string
    {
        return match ($status) {
            'paid' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            'unpaid' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
            'processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        };
    }

    public function fulfillmentLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'To prepare',
            'processing' => 'Preparing',
            'shipped' => 'Shipped',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => Str::headline((string) ($status ?? 'Unknown')),
        };
    }

    public function formatAddress(?string $address): string
    {
        $address = preg_replace('/\s+/u', ' ', trim((string) $address)) ?? '';

        return Str::limit($address, 38);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.orders-widget');
    }
}
