# Laravel catalog schema

Target stack:

- Laravel
- PostgreSQL
- Filament v5
- Spatie Laravel Media Library v11
- Spatie Laravel Translatable

The schema is normalized from `products.json`; extraction/debug metadata is intentionally not copied into production tables.

## Models

- `Category`
- `Product`

Images are stored only through Spatie Media Library in the `images` collection.

## Translations

Translatable fields use `spatie/laravel-translatable` and PostgreSQL `jsonb`.

Laravel locale keys are normalized to lowercase:

```json
{
  "fr": "...",
  "en": "...",
  "ro": "..."
}
```

`Product::$translatable`:

- `name`
- `slug`
- `tag`
- `description`
- `details`
- `contents`

`Category::$translatable`:

- `name`
- `description`
- `use`
- `price_label`

## Slugs

Product slugs are globally unique per locale, never merely unique inside a category.

PostgreSQL unique expression indexes enforce this directly for `fr`, `en` and `ro`.

The four duplicate source slugs were corrected in `products.json`:

- `pink-sparkly` → `pink-sparkly-base`
- `natural-muse` → `natural-muse-base`
- `cocoa-pink` → `cocoa-pink-base`
- `ultra-white` → `ultra-white-base`

These changes apply to the Base Camouflage records only.

## Category mapping

The source category IDs (`gel_corex`, `base_camouflage`, etc.) are preserved directly as string primary keys.

Real category content is normalized into:

- `name` JSONB
- `description` JSONB
- `use` JSONB where present
- `price_label` JSONB where present
- `parent_id`

No invented sorting, SEO, code or generic data columns are added to categories.

## Product mapping

Real product content is normalized into:

- `category_id`
- `active` — normalized from source `status === active`
- `featured`
- `is_new`
- `sku` — nullable; only source demo records currently contain values
- `name` JSONB
- `slug` JSONB
- `tag` JSONB
- `description` JSONB
- `claims` JSONB
- `details` JSONB
- `size` JSONB
- `measurements` JSONB
- `options` JSONB
- `contents` JSONB
- `price`
- `price_min` / `price_max`
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
