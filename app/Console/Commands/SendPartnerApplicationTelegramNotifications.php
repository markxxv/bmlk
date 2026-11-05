<?php

namespace App\Console\Commands;

use App\Jobs\SendPartnerApplicationTelegramReminder;
use App\Models\RoleApplication;
use App\Services\PartnerApplicationTelegramNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPartnerApplicationTelegramNotifications extends Command
{
    protected $signature = 'partners:telegram-notifications';

    protected $description = 'Retry initial partner alerts directly and enqueue daily pending reminders';

    public function handle(PartnerApplicationTelegramNotifier $notifier): int
    {
        if (blank(config('services.telegram.bot_token')) || blank(config('services.telegram.chat_id'))) {
            $this->warn('Set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID to enable notifications.');

            return self::FAILURE;
        }

        // The initial alert is always sent synchronously, including retries.
        RoleApplication::query()
            ->where('status', 'pending')
            ->whereNull('telegram_first_sent_at')
            ->chunkById(100, function ($applications) use ($notifier): void {
                foreach ($applications as $application) {
                    try {
                        $notifier->send($application->id);
                    } catch (Throwable) {
                        $this->error('Initial Telegram alert failed for application #'.$application->id);
                    }
                }
            });

        $cutoff = now()->subDay();
        $staleQueueCutoff = now()->subMinutes(20);

        RoleApplication::query()
            ->where('status', 'pending')
            ->whereNotNull('telegram_first_sent_at')
            ->where('telegram_last_sent_at', '<=', $cutoff)
            ->where(function ($query) use ($staleQueueCutoff): void {
                $query->whereNull('telegram_reminder_queued_at')
                    ->orWhere('telegram_reminder_queued_at', '<=', $staleQueueCutoff);
            })
            ->chunkById(100, function ($applications) use ($cutoff, $staleQueueCutoff): void {
                foreach ($applications as $application) {
                    $queuedAt = now();

                    // Claim the daily dispatch atomically across concurrent cron runs.
                    $claimed = RoleApplication::query()
                        ->whereKey($application->id)
                        ->where('status', 'pending')
                        ->whereNotNull('telegram_first_sent_at')
                        ->where('telegram_last_sent_at', '<=', $cutoff)
                        ->where(function ($query) use ($staleQueueCutoff): void {
                            $query->whereNull('telegram_reminder_queued_at')
                                ->orWhere('telegram_reminder_queued_at', '<=', $staleQueueCutoff);
                        })
                        ->update(['telegram_reminder_queued_at' => $queuedAt]);

                    if ($claimed !== 1) {
                        continue;
                    }

                    try {
                        SendPartnerApplicationTelegramReminder::dispatch($application->id);
                    } catch (Throwable $exception) {
                        RoleApplication::query()->whereKey($application->id)
                            ->where('telegram_reminder_queued_at', $queuedAt)
                            ->update(['telegram_reminder_queued_at' => null]);

                        Log::error('Queueing partner Telegram reminder failed.', [
                            'application_id' => $application->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        return self::SUCCESS;
    }
}
