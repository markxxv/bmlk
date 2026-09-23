# BLACK MILK — Project Context

## Current status

This repository is the current BLACK MILK Laravel application deployed from the new VPS.

The repository was rebuilt from the VPS working project and pushed to:

`markxxv/bmlk`

The product catalog has already been imported into the production database, including categories, products and available product media.

The original client/demo materials and extracted catalog source are kept separately under:

```text
design_prototype/
```

Do not treat files inside `design_prototype/` as the application itself.

## Stack

- Laravel 13
- PHP 8.3+
- PostgreSQL
- Filament v5
- Spatie Laravel Media Library v11
- Spatie Laravel Translatable v6
- `mallardduck/blade-lucide-icons`
- Vite

## Core application structure

```text
app/
├── Console/Commands/ImportCatalog.php
├── Filament/Resources/
│   ├── Categories/
│   └── Products/
└── Models/
    ├── Category.php
    └── Product.php

database/migrations/
├── 2026_09_18_000001_create_categories_table.php
├── 2026_09_18_000002_create_products_table.php
└── 2026_09_22_185746_create_media_table.php
```

## Model conventions

Never use `$fillable`.

All project models use:

```php
protected $guarded = ['id'];
```

Database IDs are normal Laravel numeric auto-incrementing IDs.

Do not introduce string IDs or UUIDs unless explicitly requested.

## Product

`App\Models\Product`

Uses:

- `HasTranslations`
- `InteractsWithMedia`
- `SoftDeletes`

Translatable JSONB fields:

- `name`
- `slug`
- `tag`
- `description`
- `details`
- `contents`
- `meta_title`
- `meta_description`

Other catalog fields include:

- `category_id`
- `active`
- `featured`
- `is_new`
- `sku`
- `claims`
- `size`
- `measurements`
- `options`
- `price`
- `price_min`
- `price_max`
- `compare_at_price`

Currency is EUR at project level. Do not add a per-product currency field unless requirements change.

### Product slugs

Product `slug` is a Spatie Translatable JSONB field.

Slug values must be globally unique for each locale.

PostgreSQL currently enforces uniqueness for:

- `fr`
- `en`
- `ro`

Do not weaken slug uniqueness to category-scoped uniqueness.

## Category

`App\Models\Category`

Uses:

- `HasTranslations`
- `SoftDeletes`

Categories use standard numeric auto-incrementing IDs.

Current translatable fields include:

- `name`
- `slug`
- `description`
- `use`
- `price_label`
- `meta_title`
- `meta_description`

Hierarchy is handled with nullable `parent_id`.

Category slugs are globally unique per locale and back the public SEO routes:

```text
/shop
/shop/category/{slug}
/shop/product/{slug}
```

Do not implement category filtering with a category query-string parameter. Category selection must resolve to its own canonical URL.

Product pages use server-rendered semantic HTML, canonical URLs, Product/Offer JSON-LD, BreadcrumbList JSON-LD and Open Graph product metadata.

Catalog discovery endpoint:

```text
/sitemap.xml
```

The sitemap includes category URLs, active product URLs and product images.

## Representative

`App\Models\Representative`

Uses:

- `HasTranslations`

Fields:

- `active`
- `sort`
- `name`
- `country`
- `tag`
- `city`
- `cta`
- `url`
- `instagram`

Translatable JSONB fields:

- `country`
- `tag`
- `city`
- `cta`

Seed data is stored in:

```text
database/seeders/RepresentativeSeeder.php
```

The seeder is idempotent and can be safely re-run.

## Media

Product images must always use Spatie Laravel Media Library.

Do not add a custom ProductImage model/table.

Collection:

```text
images
```

Current conversion:

```text
thumb — 400×400 WebP, quality 82
```

The Filament product table intentionally displays only the first image.

## Catalog import

Importer:

```text
app/Console/Commands/ImportCatalog.php
```

Command:

```bash
php artisan catalog:import
```

Default import paths:

```text
storage/app/import/catalog/products.json
storage/app/import/catalog/images/
```

Expected image subdirectories currently include:

```text
sw/
pots/
basetop/
basetop2/
bottles/
brand/
tools/
boxes/
```

The importer:

- validates category references;
- rejects duplicate source slugs;
- imports categories first;
- maps source category keys to numeric DB IDs;
- imports products;
- imports translations;
- imports price ranges and structured product data;
- imports images into Spatie Media Library;
- clears/rebuilds a product media collection during re-import;
- reports missing image files;
- shows progress bars for categories and products.

Operational status: the catalog has already been imported into the database.

## Filament v5 conventions

Admin panel:

```text
/admin
```

Shop resources:

- Product
- Category

Navigation group:

```text
Shop
```

Icons use Blade Lucide names:

```php
'lucide-package'
'lucide-folder'
'lucide-euro'
```

Do not use Tabler icon enums for this project.

### Required form layout

For Filament v5 Resource forms, use the established full-width 2/3 + 1/3 layout.

The root grid must always end with `->columnSpanFull()`:

```php
Grid::make([
    'default' => 1,
    'xl' => 3,
    '2xl' => 3,
])
    ->schema([
        Grid::make(1)
            ->schema([
                // Main content
            ])
            ->columnSpan([
                'default' => 1,
                'xl' => 2,
                '2xl' => 2,
            ]),

        Grid::make(1)
            ->schema([
                // Sidebar
            ])
            ->columnSpan([
                'default' => 1,
                'xl' => 1,
                '2xl' => 1,
            ]),
    ])
    ->columnSpanFull();
```

This is a project convention. Do not omit the final `columnSpanFull()`.

## Translations

Current catalog locales:

```text
fr
en
ro
```

Spatie Translatable fields are stored as PostgreSQL JSONB objects, for example:

```json
{
  "fr": "Produit",
  "en": "Product",
  "ro": "Produs"
}
```

## Source catalog / prototype

The original extracted client data is retained at:

```text
design_prototype/products.json
```

Related prototype/demo files are also under `design_prototype/`.

This directory is reference/source material only.

## Repository / deployment

The current GitHub repository is:

```text
markxxv/bmlk
```

The new VPS authenticates to GitHub using a repository-specific writable Deploy Key.

The VPS project is now the canonical working copy that was pushed to `main`.

A server deployment/update pipeline has not yet been finalized and is the next infrastructure task.

## Frontend design rules

These rules are mandatory for all public Blade / Tailwind UI work:

- Use Tailwind CSS v4 as natively as possible.
- Prefer built-in Tailwind utilities over arbitrary values whenever a native utility exists.
- Use built-in shadow classes such as `shadow-sm`, `shadow-md`, etc. Do not create custom arbitrary box shadows unless there is no reasonable native equivalent and it is explicitly requested.
- Use Tailwind's native typography scale such as `text-xs`, `text-sm`, `text-base`, `text-xl`, `text-2xl`, etc. Do not use arbitrary font sizes like `text-[11px]` when a native size is suitable.
- Prefer solid Tailwind palette colors over opacity-based color syntax. For example, prefer `text-zinc-200` instead of `text-black/20`, and `border-zinc-200` instead of `border-black/10`.
- Prefer native Tailwind color families such as `zinc`, `stone`, `neutral`, `white`, `black`, plus the project's intentional brand colors when they are required.
- Keep arbitrary values rare and deliberate. Use them only for genuinely project-specific values that Tailwind does not represent cleanly.
- Wrap every user-facing UI string in Blade's translation helper, including navigation labels, buttons, headings, helper text, accessibility labels and screen-reader text: `{{ __('...') }}`. Do not hard-code visible UI copy directly in Blade.

## Working rules

- Keep implementation minimal and project-specific.
- Do not invent generic ecommerce fields that are not required by the real catalog.
- Never use `$fillable`; use `protected $guarded = ['id'];`.
- Use numeric auto-increment IDs.
- Use Spatie Media Library for images.
- Use Spatie Translatable JSONB for translated fields.
- Keep product slugs globally unique per locale.
- Use Lucide icons in Filament.
- Preserve the established Filament v5 full-width layout.
- Treat `design_prototype/` as source/reference material, not runtime application code.


## Product JavaScript

Product-only frontend JavaScript lives in:

```text
resources/js/product.js
```

It is a separate Vite entry and must only be loaded from the product detail view with:

```blade
@vite('resources/js/product.js')
```

The product gallery uses `embla-carousel`. Do not import Embla into `resources/js/app.js`; pages other than product detail must not download the carousel bundle.


## Event

`App\Models\Event`

Fields:

- `active`
- `sort`
- `name`
- `url`
- `type`
- `description`
- `starts_at`
- `ends_at`
- `country`
- `city`
- `address`
- `price`
- `ticket_url`

Translatable JSONB fields:

- `name`
- `url`
- `description`

`type` is a stable non-translated code such as `training`, `workshop`, `international_tour`, or `event`; labels belong in translation files.

Prices are always EUR, so no currency column is stored.

Sales logic:

- `ticket_url = null` — BLACK MILK sells the ticket internally.
- `ticket_url != null` — ticket purchase goes to the external platform.

`url` is reserved for future event detail pages and is unique per locale.


## Events frontend

- `GET /events` → public events listing page.
- Main navigation links to `route('events')`.
- Home and events page use the same upcoming-events query and date relevance ordering.
- Event type filtering is client-side with Alpine.js and no transition animation.
