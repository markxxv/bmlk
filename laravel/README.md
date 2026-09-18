# Laravel catalog schema

Target stack:

- Laravel
- PostgreSQL
- Filament v5
- Spatie Laravel Media Library v11

The schema is normalized from `products.json`; extraction/debug metadata is intentionally not copied into production tables.

## Models

- `Category`
- `Product`

Images are stored only through Spatie Media Library in the `images` collection.

## Category mapping

The source category IDs (`gel_corex`, `base_camouflage`, etc.) are preserved directly as string primary keys.

Real category content is normalized into:

- `name` JSONB — FR / EN / RO
- `description` JSONB — FR / EN / RO
- `use` JSONB — FR / EN / RO where present
- `price_label` JSONB — source category price text where present
- `parent_id` — source hierarchy field

No invented `active`, sorting, SEO, code or generic data columns are added to categories.

## Product mapping

Real product content is normalized into:

- `category_id`
- `active` — normalized from source `status === active`
- `featured`
- `is_new`
- `sku` — nullable; only source demo records currently contain values
- `name` JSONB — FR / EN / RO
- `slug`
- `tag` JSONB
- `description` JSONB
- `claims` JSONB
- `details` JSONB
- `size` JSONB
- `measurements` JSONB
- `options` JSONB
- `contents` JSONB for sets
- `price`
- `price_min` / `price_max` for real ranged prices such as 5–20 €
- `compare_at_price`

Laravel infrastructure fields `id`, timestamps and soft deletes are retained.

The following extraction-only fields from `products.json` are deliberately not persisted: `catalog_scope`, `product_type`, `source_files`, `source_payload`, `source_payloads`, `embedded_swatch`, `legacy_demo`, `legacy_demo_inventory`.

Currency and tax flags are not stored because the project uses EUR and catalog prices are treated consistently at project level.

## Media

`Product` implements Spatie `HasMedia`.

Collection:

```php
images
```

Conversion:

```php
thumb: 400x400 WebP, quality 82
```

For Filament v5:

```php
SpatieMediaLibraryFileUpload::make('images')
    ->collection('images')
    ->multiple()
    ->reorderable()
    ->conversion('thumb');
```

The 10 course records under `service_offerings` are intentionally outside the physical product catalog schema.
