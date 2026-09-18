# Laravel catalog schema

Target stack:

- Laravel
- PostgreSQL
- Filament v5
- Spatie Laravel Media Library v11
- Filament Spatie Media Library plugin

## Models

- `Category`
- `Product`

No `ProductImage` model. Product images live in the Spatie Media Library `images` collection.

## Conventions

- Models use only `protected $guarded = ['id'];`.
- Translatable fields are JSONB: `name`, `slug`, `description`, `meta_title`, `meta_description`.
- `tags`, `claims`, `details`, `size`, `measurements`, `options` are JSONB.
- Prices use `numeric(12,2)`.
- `source_key` is reserved for idempotent imports from `products.json`.
- `source_data` and `legacy_data` preserve source/demo data.
- Product media collection: `images`.
- Media conversion: `thumb`, 400×400 WebP, quality 82.
- Media order is handled by Spatie's `order_column`, which works with Filament reordering.

## Filament v5 media field

Use the Spatie field directly in the future Product resource:

```php
SpatieMediaLibraryFileUpload::make('images')
    ->collection('images')
    ->multiple()
    ->reorderable()
    ->conversion('thumb');
```

The current `products.json` also contains courses under `service_offerings`. They remain separate from the physical catalog for now.
