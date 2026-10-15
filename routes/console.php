<?php

use Illuminate\Support\Facades\Schedule;

// The server runs schedule:run every minute; emails are sent synchronously.
Schedule::command('orders:payment-emails')->everyMinute()->withoutOverlapping(10);
