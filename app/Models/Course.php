<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

class Course extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    // Telegram links (telegram_channel_url, telegram_chat_url) are private: they are
    // only rendered in the account of customers who bought the course.

    public array $translatable = ['tag', 'description', 'points', 'programme', 'info_pdf'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'active' => 'boolean',
            'sort' => 'integer',
        ];
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
