<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected function formattedPrice(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->price !== null
                ? self::formatPriceValue($this->price)
                : null
        );
    }

    protected function formattedPriceMin(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->price_min !== null
                ? self::formatPriceValue($this->price_min)
                : null
        );
    }

    protected function formattedPriceMax(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->price_max !== null
                ? self::formatPriceValue($this->price_max)
                : null
        );
    }

    protected function formattedCompareAtPrice(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->compare_at_price !== null
                ? self::formatPriceValue($this->compare_at_price)
                : null
        );
    }

    public static function formatPriceValue(mixed $value): string
    {
        $price = round((float) $value, 2);
        $decimals = abs($price - round($price)) < 0.00001 ? 0 : 2;

        return number_format($price, $decimals, ',', ' ');
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
        $this->addMediaCollection('images')->useDisk('public');
    }

    /**
     * Serve generated WebP variants only. Existing thumbnails keep the storefront
     * functional until new conversions have been regenerated on older media.
     */
    public function storefrontImageUrl(string $conversion = 'card'): string
    {
        $media = $this->getFirstMedia('images');

        if ($media === null) {
            return '';
        }

        foreach (array_unique([$conversion, 'thumb']) as $name) {
            if ($media->hasGeneratedConversion($name)) {
                return $media->getUrl($name);
            }
        }

        return '';
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections('images')
            ->fit(Fit::Contain, 400, 400)
            ->format('webp')
            ->quality(82)
            ->nonQueued();

        // Preserve the source image ratio; storefront CSS handles cropping.
        $this->addMediaConversion('card')
            ->performOnCollections('images')
            ->fit(Fit::Max, 800, 1067)
            ->format('webp')
            ->quality(80)
            ->nonQueued();

        $this->addMediaConversion('detail')
            ->performOnCollections('images')
            ->fit(Fit::Max, 1200, 1200)
            ->format('webp')
            ->quality(85)
            ->nonQueued();

        // The product gallery occupies up to roughly half the desktop content
        // width. Give Retina screens a true 2x source without forcing large
        // downloads on 1x displays.
        $this->addMediaConversion('detail_retina')
            ->performOnCollections('images')
            ->fit(Fit::Max, 2400, 2400)
            ->format('webp')
            ->quality(85)
            ->nonQueued();
    }
}
