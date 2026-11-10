<?php

namespace App\Console\Commands;

use App\Mail\ReviewRequestMail;
use App\Models\Order;
use App\Models\ReviewAccessToken;
use App\Models\ReviewEmailOptOut;
use App\Support\ReviewSendWindow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class SendReviewRequests extends Command
{
    protected $signature = 'orders:send-review-requests';

    protected $description = 'Send one post-delivery rating email seven days after actual delivery';

    public function handle(): int
    {
        $now = now();

        if (! ReviewSendWindow::isOpen($now)) {
            $this->line('Outside French business hours or on a public holiday.');
            return self::SUCCESS;
        }

        if (app()->isProduction() && in_array(config('mail.default'), ['log', 'array'], true)) {
            $this->error('Configure a real MAIL_MAILER before sending review emails.');
            return self::FAILURE;
        }

        Order::query()
            ->where('ratings_enabled', true)
            ->where('payment_status', 'paid')
            ->whereIn('status', ['completed', 'shipped', 'processing'])
            ->where(fn ($query) => $query->where('status', 'completed')
                ->orWhere('sendcloud_tracking_phase', 'delivered'))
            ->whereNotNull('email')
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', $now->copy()->subDays(7))
            ->whereNull('review_request_sent_at')
            ->withRefundBalance()
            ->with('ratings')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    if (! $order->hasUnratedItems() || ReviewEmailOptOut::contains($order->email)) {
                        continue;
                    }

                    $this->sendOnce($order);
                }
            });

        return self::SUCCESS;
    }

    private function sendOnce(Order $order): void
    {
        // Claim before SMTP to prevent duplicate sends from overlapping workers.
        $claimedAt = now();
        $claimed = Order::query()
            ->whereKey($order->id)
            ->where('ratings_enabled', true)
            ->where('payment_status', 'paid')
            ->whereNull('review_request_sent_at')
            ->update(['review_request_sent_at' => $claimedAt]);

        if ($claimed !== 1) {
            return;
        }

        try {
            $token = bin2hex(random_bytes(32));
            ReviewAccessToken::query()->updateOrCreate(
                ['order_id' => $order->id],
                ['token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(30)],
            );

            $reviewUrl = route('reviews.show', ['token' => $token]);
            $unsubscribeUrl = URL::temporarySignedRoute(
                'reviews.unsubscribe',
                now()->addYear(),
                ['order' => $order->id],
            );

            Mail::to($order->email)->send(new ReviewRequestMail($order, $reviewUrl, $unsubscribeUrl));
            $this->info("Review request sent: {$order->order_number}");
        } catch (Throwable $exception) {
            // Retry on the next eligible scheduled run without claiming success.
            Order::query()->whereKey($order->id)
                ->where('review_request_sent_at', $claimedAt)
                ->update(['review_request_sent_at' => null]);

            Log::error('Review request email failed.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);
            $this->error("Review request failed for {$order->order_number}");
        }
    }
}
