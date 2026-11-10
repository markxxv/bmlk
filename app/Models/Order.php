<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'ratings_enabled' => 'boolean',
            'delivered_at' => 'datetime',
            'review_request_sent_at' => 'datetime',
            'shipping_parcel' => 'array',
            'sendcloud_rate' => 'decimal:2',
            'sendcloud_event_at' => 'integer',
            'sendcloud_tracking_synced_at' => 'datetime',
            'sendcloud_expected_delivery_date' => 'date',
            'subtotal' => 'decimal:2',
            'delivery_cost' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'payment_failed_at' => 'datetime',
            'paid_email_sent_at' => 'datetime',
            'failed_email_sent_at' => 'datetime',
            'failed_reminder_sent_at' => 'datetime',
        ];
    }

    public function customerStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => __('En attente de préparation'),
            'processing' => __('En préparation'),
            'shipped' => __('Expédiée'),
            'completed' => __('Terminée'),
            'cancelled' => __('Annulée'),
            'archived' => __('Archivée'),
            default => __('En attente'),
        };
    }

    public function customerPaymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'paid' => __('Payée'),
            'processing' => __('Paiement en cours'),
            'failed' => __('Paiement échoué'),
            default => __('Paiement en attente'),
        };
    }

    public function scopeWithRefundBalance(Builder $query): Builder
    {
        return $query->whereRaw(
            "COALESCE((SELECT SUM(amount_cents) FROM order_refunds WHERE order_refunds.order_id = orders.id AND order_refunds.status NOT IN ('failed', 'canceled')), 0) < ROUND(orders.total_amount * 100)"
        );
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(OrderTrackingEvent::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(ProductRating::class);
    }

    public function reviewAccessToken(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ReviewAccessToken::class);
    }

    public function canBeRated(): bool
    {
        return $this->ratings_enabled === true
            && $this->payment_status === 'paid'
            && $this->status !== 'cancelled'
            && ($this->status === 'completed' || $this->sendcloud_tracking_phase === 'delivered');
    }

    /** Preserve order snapshot line indices, even for inactive catalog items. */
    public function rateableItems(): array
    {
        return collect($this->items ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && (int) ($item['product_id'] ?? 0) > 0)
            ->all();
    }

    public function hasUnratedItems(): bool
    {
        if (! $this->canBeRated()) {
            return false;
        }

        $ratedIndices = $this->ratings->pluck('order_item_index')->map(fn ($index): int => (int) $index)->all();

        foreach (array_keys($this->rateableItems()) as $index) {
            if (! in_array($index, $ratedIndices, true)) {
                return true;
            }
        }

        return false;
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(OrderRefund::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'delivery_code', 'code');
    }

    protected static function booted(): void
    {
        static::created(function (Order $order): void {
            $order->forceFill([
                'order_number' => sprintf(
                    'BM-%s-%06d',
                    $order->created_at->format('Ymd'),
                    $order->id,
                ),
            ])->saveQuietly();
        });
    }
}
