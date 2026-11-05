<?php

namespace App\Jobs;

use App\Services\PartnerApplicationTelegramNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPartnerApplicationTelegramReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public int $applicationId)
    {
        $this->onConnection('database');
        $this->onQueue('partner-telegram');
    }

    public function handle(PartnerApplicationTelegramNotifier $notifier): void
    {
        // The notifier atomically checks that the application is still pending
        // and at least 24 hours have elapsed since the last successful send.
        $notifier->send($this->applicationId, true);
    }
}
