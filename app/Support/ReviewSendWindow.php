<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class ReviewSendWindow
{
    public static function isOpen(CarbonInterface $date): bool
    {
        $paris = CarbonImmutable::instance($date)->setTimezone('Europe/Paris');

        if ($paris->isWeekend() || $paris->hour < 10 || $paris->hour >= 19) {
            return false;
        }

        return ! in_array($paris->toDateString(), self::holidays($paris->year), true);
    }

    /** French nationwide public holidays; regional exceptions are not used. */
    private static function holidays(int $year): array
    {
        $easter = self::easterSunday($year);

        return array_merge(
            array_map(fn (string $date): string => "$year-$date", [
                '01-01', '05-01', '05-08', '07-14', '08-15', '11-01', '11-11', '12-25',
            ]),
            [
                $easter->addDay()->toDateString(),     // Easter Monday
                $easter->addDays(39)->toDateString(),  // Ascension Thursday
                $easter->addDays(50)->toDateString(),  // Whit Monday
            ],
        );
    }

    /** Gregorian computus, independent of PHP's optional calendar extension. */
    private static function easterSunday(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = ($h + $l - 7 * $m + 114) % 31 + 1;

        return CarbonImmutable::create($year, $month, $day, 0, 0, 0, 'Europe/Paris');
    }
}
