<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingBox extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'inner_length_cm' => 'decimal:2',
            'inner_width_cm' => 'decimal:2',
            'inner_height_cm' => 'decimal:2',
            'outer_length_cm' => 'decimal:2',
            'outer_width_cm' => 'decimal:2',
            'outer_height_cm' => 'decimal:2',
            'tare_weight_g' => 'integer',
            'max_weight_g' => 'integer',
            'active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    // Only physically measured and enabled boxes can be used for quotes.
    public function scopeReady(Builder $query): Builder
    {
        return $query->where('active', true)
            ->where('tare_weight_g', '>', 0)
            ->where('inner_length_cm', '>', 0)
            ->where('inner_width_cm', '>', 0)
            ->where('inner_height_cm', '>', 0)
            ->where('outer_length_cm', '>', 0)
            ->where('outer_width_cm', '>', 0)
            ->where('outer_height_cm', '>', 0)
            ->orderBy('sort');
    }
}
