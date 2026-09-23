<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Event extends Model
{
    use HasFactory, HasTranslations;

    public const SALES_INTERNAL = 'internal';

    public const SALES_EXTERNAL = 'external';

    protected $guarded = ['id'];

    public array $translatable = [
        'name',
        'url',
        'type',
        'status',
        'description',
        'cta',
        'date_label',
        'country',
        'city',
        'venue',
        'address',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'confirmed' => 'boolean',
            'sort' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'price' => 'decimal:2',
        ];
    }
}
