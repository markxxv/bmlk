# Laravel catalog schema

PostgreSQL-oriented schema extracted from `products.json`.

## Models

- `Category`
- `Product`
- `ProductImage`

## Decisions

- Translatable fields are JSONB: `name`, `slug`, `description`, `meta_title`, `meta_description`, `alt`.
- Product `tags`, `claims`, `details`, `size`, `measurements` and `options` are JSONB.
- Product options are intentionally not separate models yet: the current catalog only needs lightweight structured options.
- Prices use PostgreSQL-compatible `numeric(12,2)`.
- `source_key` is intended for idempotent imports from `products.json`.
- `source_data` and `legacy_data` keep source/demo information without polluting normalized columns.
- Images are separate rows because ordering, role and future storage disk changes belong to the image itself.

The current `products.json` also contains courses under `service_offerings`. They are intentionally not modeled as products here.
