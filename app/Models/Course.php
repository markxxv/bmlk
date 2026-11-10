<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Course extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia;

    protected $guarded = ['id'];

    // Telegram links (telegram_channel_url, telegram_chat_url) are private: they are
    // only rendered in the account of customers who bought the course.

    public array $translatable = ['name', 'tag', 'description', 'points', 'programme', 'info_pdf'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Course $course): void {
            if (blank($course->slug)) {
                $name = $course->getTranslation('name', 'fr', false) ?: collect($course->getTranslations('name'))->filter()->first();
                $base = Str::slug((string) $name) ?: 'course';
                $slug = $base;

                for ($i = 2; static::query()->where('slug', $slug)->exists(); $i++) {
                    $slug = "{$base}-{$i}";
                }

                $course->slug = $slug;
            }

            if (! $course->sort) {
                $course->sort = ((int) static::query()->max('sort')) + 10;
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile()->useDisk('public');
    }

    /** Uploaded cover, falling back to the legacy file name in public/img/formations. */
    public function imageUrl(): ?string
    {
        $media = $this->getFirstMedia('image');

        if ($media) {
            return $media->getUrl();
        }

        return $this->image ? asset('img/formations/'.$this->image) : null;
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(CoursePurchase::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeMode(Builder $query, string $mode): Builder
    {
        return $query->where('mode', $mode)->orderBy('sort')->orderBy('id');
    }

    public function isLive(): bool
    {
        return $this->mode === 'live';
    }

    /** Only online courses with a price can be bought in one click. */
    public function isPurchasable(): bool
    {
        return $this->active && ! $this->isLive() && $this->price !== null && (float) $this->price > 0;
    }

    public function priceCents(): int
    {
        return (int) round((float) $this->price * 100);
    }

    public function programmeData(): ?array
    {
        $programme = $this->getTranslation('programme', app()->getLocale());

        return is_array($programme) && $programme !== [] ? $programme : null;
    }

    public function infoPdfUrl(): ?string
    {
        $path = $this->getTranslation('info_pdf', app()->getLocale());

        return filled($path) ? Storage::disk('public')->url($path) : null;
    }
}
