<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PartnerPayout;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Partner earnings = discount given on paid, non-cancelled orders placed with the
 * partner's promo code. Owed = earned - recorded payouts.
 */
class PartnerBalance
{
    /** Paid, non-cancelled orders of the partner's promo code. */
    public static function orders(User $user): Builder
    {
        return Order::query()
            ->whereRaw('UPPER(promo_code) = ?', [Str::upper((string) $user->influencer_code)])
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled');
    }

    public static function earned(User $user): float
    {
        return blank($user->influencer_code) ? 0.0 : round((float) self::orders($user)->sum('discount_amount'), 2);
    }

    public static function paid(User $user): float
    {
        return round((float) PartnerPayout::query()->where('user_id', $user->id)->sum('amount'), 2);
    }

    /** Can be negative if the partner was overpaid. */
    public static function owed(User $user): float
    {
        return round(self::earned($user) - self::paid($user), 2);
    }

    public static function money(float $value): string
    {
        return number_format($value, 2, '.', ' ').' €';
    }
}
