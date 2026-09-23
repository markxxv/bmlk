<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        ];
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
