<?php

namespace App\Filament\Widgets;

use App\Models\RoleApplication;
use App\Services\PartnerBalance;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

/** Edit-page widget: what we owe this partner today. */
class PartnerBalanceStats extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected static bool $isDiscovered = false;

    protected int|array|null $columns = 3;

    protected function getStats(): array
    {
        $user = $this->record instanceof RoleApplication ? $this->record->user : null;
        if (! $user || $this->record->role !== 'influencer' || blank($user->influencer_code)) {
            return [];
        }

        $earned = PartnerBalance::earned($user);
        $paid = PartnerBalance::paid($user);
        $owed = PartnerBalance::owed($user);

        return [
            Stat::make('Earned (discounts)', PartnerBalance::money($earned))
                ->description('All sales by promo code')
                ->descriptionIcon('lucide-ticket-percent')
                ->color('gray'),
            Stat::make('Already paid', PartnerBalance::money($paid))
                ->description('Recorded payouts')
                ->descriptionIcon('lucide-check-check')
                ->color('success'),
            Stat::make('We owe today', PartnerBalance::money(max($owed, 0)))
                ->description($owed < 0 ? 'Overpaid by '.PartnerBalance::money(-$owed) : ($owed > 0 ? 'Waiting for payout' : 'Nothing to pay'))
                ->descriptionIcon($owed > 0 ? 'lucide-wallet' : 'lucide-circle-check')
                ->color($owed > 0 ? 'warning' : 'gray'),
        ];
    }
}
