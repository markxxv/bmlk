<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\RoleApplications\RoleApplicationResource;
use App\Models\Order;
use App\Models\RoleApplication;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShopOverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = -10;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $now = CarbonImmutable::now('Europe/Paris');
        $monthStart = $now->startOfMonth();

        // paid_at is stored as UTC. The reporting month/day follows Paris time,
        // including daylight saving changes; unpaid checkouts are never sales.
        $paidDates = Order::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $monthStart->utc()->toDateTimeString())
            ->where('paid_at', '<=', $now->utc()->toDateTimeString())
            ->pluck('paid_at');

        $dailySales = array_fill(1, $now->day, 0);

        foreach ($paidDates as $paidAt) {
            $day = CarbonImmutable::parse($paidAt, 'UTC')->setTimezone('Europe/Paris')->day;
            $dailySales[$day]++;
        }

        $pendingApplications = RoleApplication::query()
            ->where('status', 'pending')
            ->count();

        $paidPendingOrders = Order::query()
            ->where('status', 'pending')
            ->where('payment_status', 'paid')
            ->count();

        return [
            Stat::make('Partner applications', $pendingApplications)
                ->description('Awaiting review')
                ->descriptionIcon('lucide-clock-3')
                ->color($pendingApplications > 0 ? 'warning' : 'gray')
                ->url(RoleApplicationResource::getUrl('index')),

            Stat::make('Paid orders pending', $paidPendingOrders)
                ->description('Awaiting preparation')
                ->descriptionIcon('lucide-package')
                ->color($paidPendingOrders > 0 ? 'warning' : 'gray')
                ->url(OrderResource::getUrl('index')),

            Stat::make('Sales this month', $paidDates->count())
                ->description($now->format('F Y').' · Paid orders')
                ->descriptionIcon('lucide-trending-up')
                ->chart(array_values($dailySales))
                ->color('success')
                ->url(OrderResource::getUrl('index')),
        ];
    }
}
