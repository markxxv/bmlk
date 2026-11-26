<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The server already runs "php artisan schedule:run" every minute.
Schedule::command('orders:payment-emails')->everyMinute()->withoutOverlapping(10);

// One post-delivery rating request, only during French business hours.
// The command independently excludes holidays and rechecks its time window.
Schedule::command('orders:send-review-requests')
    ->everyFifteenMinutes()
    ->weekdays()
    ->between('10:00', '18:59')
    ->timezone('Europe/Paris')
    ->withoutOverlapping(15);
// Immediate paid-order alerts are retried hourly, even outside business hours.
Schedule::command('orders:telegram-notifications --initial-only')->hourly()->withoutOverlapping(10);

// Existing pending paid orders are checked every workday at 10:00 Paris time.
Schedule::command('orders:telegram-notifications --reminders-only')
    ->weekdays()
    ->dailyAt('10:00')
    ->timezone('Europe/Paris')
    ->withoutOverlapping(10);

// Backfill historic shipments with --all once. Regular checks only request
// tracking for open shipments, respecting a minimum sync interval.
Schedule::command('shipping:sync-tracking')->hourly()->withoutOverlapping(30);

// First partner alerts and any failed retries are sent directly. Only reminders
// are queued (database driver), and the short-lived worker drains that queue.
Schedule::command('partners:telegram-notifications')->everyFiveMinutes()->withoutOverlapping(5);
Schedule::command('queue:work database --queue=partner-telegram --stop-when-empty --max-time=45 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5);

// Queue only new checkout orders marked shipped or completed, never historical orders.
Schedule::command('orders:queue-invoice-emails')->everyMinute()->withoutOverlapping(5);

// Individual customer invoice PDFs are queued after payment. Historical paid
// orders are also issued on the first authenticated download, without waiting.
Schedule::command('queue:work database --queue=invoices --stop-when-empty --max-time=45 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5);

// Invoice ZIPs requested on the admin "Invoices" page: the HTTP request only inserts a row,
// this picks it up within a minute. runInBackground keeps a long build from delaying other tasks.
Schedule::command('invoices:build-exports')->everyMinute()->withoutOverlapping(30)->runInBackground();
Schedule::command('invoices:prune-exports')->dailyAt('03:30')->timezone('Europe/Paris')->withoutOverlapping(10);
