<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// A single system cron runs Laravel Scheduler every minute; no mail worker.
Schedule::command('orders:payment-emails')->everyMinute()->withoutOverlapping(10);
