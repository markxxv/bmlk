<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\PartnerBalance;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** List-page widget: total we owe to all approved partners today. */
class PartnersOwedStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected int|array|null $columns = 3;

    protected function getStats(): array
    {
        $partners = User::query()->where('is_influencer', true)->whereNotNull('influencer_code')->get();

        $earned = $paid = $owed = 0.0;
        $waiting = 0;
        foreach ($partners as $partner) {
            $e = PartnerBalance::earned($partner);
            $p = PartnerBalance::paid($partner);
            $earned += $e;
            $paid += $p;
            if ($e - $p > 0) {
                $owed += $e - $p;
                $waiting++;
            }
        }

        return [
            Stat::make('We owe partners today', PartnerBalance::money($owed))
                ->description($waiting.' partner(s) waiting for payout')
                ->descriptionIcon('lucide-wallet')
                ->color($owed > 0 ? 'warning' : 'gray'),
            Stat::make('Earned in total', PartnerBalance::money($earned))
                ->descriptionIcon('lucide-ticket-percent')->description('Discounts on promo-code sales')->color('gray'),
            Stat::make('Paid in total', PartnerBalance::money($paid))
                ->descriptionIcon('lucide-check-check')->description('Recorded payouts')->color('success'),
        ];
    }
}
