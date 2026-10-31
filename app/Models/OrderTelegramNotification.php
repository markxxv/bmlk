<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTelegramNotification extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'first_sent_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
