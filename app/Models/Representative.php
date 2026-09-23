<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Representative extends Model
{
    use HasFactory, HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = [
        'country',
        'tag',
        'city',
        'cta',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort' => 'integer',
        ];
    }
}
