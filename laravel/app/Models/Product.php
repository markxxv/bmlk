<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'featured' => 'boolean',
            'is_new' => 'boolean',
            'sort_order' => 'integer',
            'name' => 'array',
            'slug' => 'array',
            'description' => 'array',
            'tags' => 'array',
            'claims' => 'array',
            'details' => 'array',
            'size' => 'array',
            'measurements' => 'array',
            'options' => 'array',
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'tax_included' => 'boolean',
            'source_data' => 'array',
            'legacy_data' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections('images')
            ->fit(Fit::Contain, 400, 400)
            ->format('webp')
            ->quality(82)
            ->nonQueued();
    }
}
