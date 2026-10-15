<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'subtotal' => 'decimal:2',
            'delivery_cost' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'payment_failed_at' => 'datetime',
            'paid_email_sent_at' => 'datetime',
            'failed_email_sent_at' => 'datetime',
            'failed_reminder_sent_at' => 'datetime',
        ];
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
