<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $guarded = ['id'];

    public array $translatable = [
        'title',
        'body',
        'slug',
        'meta_title',
        'meta_description',
    ];

    public function getTranslatableSlugSourceAttribute(): string
    {
        return 'title';
    }

    public function getTranslatableSlugTargetAttribute(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort' => 'integer',
        ];
    }
}
