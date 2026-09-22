# Laravel catalog schema

Target stack:

- Laravel
- PostgreSQL
- Filament v5
- Spatie Laravel Media Library v11
- Spatie Laravel Translatable

## Models

- `Category`
- `Product`

Both models use classic Laravel auto-incrementing numeric IDs.

Images are stored only through Spatie Media Library in the `images` collection.

## Translations

Translatable fields use `spatie/laravel-translatable` and PostgreSQL `jsonb`.

Locale keys:

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

Product slugs are globally unique per locale. PostgreSQL expression indexes enforce uniqueness for `fr`, `en` and `ro`.

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

## Catalog import

Command:

```bash
php artisan catalog:import
```

The command imports:

- categories;
- parent category relations;
- products;
- FR / EN / RO translations;
- prices and price ranges;
- options, sizes, measurements, claims and set contents;
- all product images through Spatie Media Library.

The command shows separate progress bars for categories and products.

### Where to put products.json

Copy the final catalog JSON to:

```text
storage/app/import/catalog/products.json
```

### Where to put images

Create:

```text
storage/app/import/catalog/images/
```

Inside it, keep exactly the same relative folders used by `products.json`:

```text
storage/app/import/catalog/
├── products.json
└── images/
    ├── sw/
    ├── pots/
    ├── basetop/
    ├── basetop2/
    ├── bottles/
    ├── brand/
    ├── tools/
    └── boxes/
```

Example:

If JSON contains:

```json
{
  "relative_path": "pots/sweet-candy.jpg"
}
```

the source file must be located at:

```text
storage/app/import/catalog/images/pots/sweet-candy.jpg
```

The importer copies source files into the configured Spatie Media Library disk. The original import files remain untouched.

Before importing media for an existing product, its `images` collection is cleared and rebuilt from JSON, so repeated imports do not create duplicate media rows.

Missing image files do not stop the catalog import. Their paths are shown in the final command summary.

### Custom paths

Defaults can be overridden:

```bash
php artisan catalog:import --json=/absolute/path/products.json --images=/absolute/path/images
```

Relative custom paths are resolved from the Laravel project root:

```bash
php artisan catalog:import --json=storage/import/products.json --images=storage/import/images
```

### Re-import behavior

The import is repeatable:

- categories are matched by their French translated name;
- products are matched by globally unique `slug.fr`;
- existing rows are updated;
- soft-deleted matching rows are restored;
- product media is rebuilt from the source image list.

The string IDs from the extraction JSON such as `gel_corex` are used only inside the importer to map products to the newly created numeric category IDs. They are not stored as database primary keys.

## Files

Copy these files into the corresponding paths of the Laravel project:

```text
app/Models/Category.php
app/Models/Product.php
app/Console/Commands/ImportCatalog.php
database/migrations/2026_09_18_000001_create_categories_table.php
database/migrations/2026_09_18_000002_create_products_table.php
database/migrations/2026_09_18_000003_create_media_table.php
```

The 10 course records under `service_offerings` are intentionally outside the physical product catalog schema.


## Filament v5 layout convention

For this project, Resource forms always use a full-width root schema grid:

```php
Grid::make([
    'default' => 1,
    'xl' => 3,
    '2xl' => 3,
])
    ->schema([
        // Main content
        Grid::make(1)
            ->schema([
                // Sections...
            ])
            ->columnSpan([
                'default' => 1,
                'xl' => 2,
                '2xl' => 2,
            ]),

        // Sidebar
        Grid::make(1)
            ->schema([
                // Settings sections...
            ])
            ->columnSpan([
                'default' => 1,
                'xl' => 1,
                '2xl' => 1,
            ]),
    ])
    ->columnSpanFull();
```

Important: the root `Grid` must always end with `->columnSpanFull()`. Without it, Filament v5 can constrain the entire 2/3 + 1/3 layout into a narrow schema column.
