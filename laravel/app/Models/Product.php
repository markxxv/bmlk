<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'active',
        'featured',
        'is_new',
        'sort_order',
        'status',
        'product_type',
        'catalog_scope',
        'source_key',
        'sku',
        'barcode',
        'name',
        'slug',
        'description',
        'tags',
        'claims',
        'details',
        'size',
        'measurements',
        'options',
        'price',
        'compare_at_price',
        'currency',
        'tax_included',
        'source_data',
        'legacy_data',
    ];

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

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }
}
