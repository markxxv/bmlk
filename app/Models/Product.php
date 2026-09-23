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
use Spatie\Translatable\HasTranslations;

class Product extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia, SoftDeletes;

    protected $guarded = ['id'];

    public array $translatable = [
        'name',
        'slug',
        'tag',
        'description',
        'details',
        'contents',
         'meta_title',
    'meta_description',
    ];

    public function getTranslatableSlugSourceAttribute(): string
    {
        return 'name';
    }

    public function getTranslatableSlugTargetAttribute(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'featured' => 'boolean',
            'is_new' => 'boolean',
            'claims' => 'array',
            'size' => 'array',
            'measurements' => 'array',
            'options' => 'array',
            'price' => 'decimal:2',
            'price_min' => 'decimal:2',
            'price_max' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
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
