<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The server already runs "php artisan schedule:run" every minute.
Schedule::command('orders:payment-emails')->everyMinute()->withoutOverlapping(10);
// Immediate paid-order alerts are retried hourly, even outside business hours.
Schedule::command('orders:telegram-notifications --initial-only')->hourly()->withoutOverlapping(10);

// Existing pending paid orders are checked every workday at 10:00 Paris time.
Schedule::command('orders:telegram-notifications --reminders-only')
    ->weekdays()
    ->dailyAt('10:00')
    ->timezone('Europe/Paris')
    ->withoutOverlapping(10);

// First partner alerts and any failed retries are sent directly. Only reminders
// are queued (database driver), and the short-lived worker drains that queue.
Schedule::command('partners:telegram-notifications')->everyFiveMinutes()->withoutOverlapping(5);
Schedule::command('queue:work database --queue=partner-telegram --stop-when-empty --max-time=45 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5);
