<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class ImportCatalog extends Command
{
    protected $signature = 'catalog:import
        {--json= : Path to products.json, relative to project root or absolute}
        {--images= : Path to image root, relative to project root or absolute}';

    protected $description = 'Import BLACK MILK categories, products and product images from products.json';

    private array $locales = ['fr', 'en', 'ro'];

    private int $importedImages = 0;

    private array $missingImages = [];

    public function handle(): int
    {
        $jsonPath = $this->resolvePath($this->option('json'), storage_path('app/import/catalog/products.json'));
        $imagesPath = $this->resolvePath($this->option('images'), storage_path('app/import/catalog/images'));

        if (! is_file($jsonPath)) {
            $this->error("JSON file not found: {$jsonPath}");

            return self::FAILURE;
        }

        if (! is_dir($imagesPath)) {
            $this->error("Images directory not found: {$imagesPath}");

            return self::FAILURE;
        }

        try {
            $data = json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->error('Invalid JSON: '.$e->getMessage());

            return self::FAILURE;
        }

        $categories = $data['categories'] ?? null;
        $products = $data['products'] ?? null;

        if (! is_array($categories) || ! is_array($products)) {
            $this->error('JSON must contain categories[] and products[].');

            return self::FAILURE;
        }

        $this->locales = collect($data['meta']['locale_source_order'] ?? $this->locales)
            ->map(fn (string $locale) => strtolower($locale))
            ->values()
            ->all();

        try {
            $this->validateCatalog($categories, $products);

            $this->newLine();
            $this->info('Importing categories');
            $categoryMap = $this->importCategories($categories);

            $this->newLine();
            $this->info('Importing products and media');
            $this->importProducts($products, $categoryMap, $imagesPath);
        } catch (RuntimeException $e) {
            $this->newLine();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Catalog import completed.');
        $this->line('Categories: '.count($categories));
        $this->line('Products: '.count($products));
        $this->line('Images imported: '.$this->importedImages);

        if ($this->missingImages !== []) {
            $this->components->warn('Missing images: '.count($this->missingImages));

            foreach (array_slice($this->missingImages, 0, 20) as $path) {
                $this->line('  - '.$path);
            }

            if (count($this->missingImages) > 20) {
                $this->line('  ... and '.(count($this->missingImages) - 20).' more');
            }
        }

        return self::SUCCESS;
    }

    private function importCategories(array $categories): array
    {
        $map = [];
        $parents = [];

        $bar = $this->output->createProgressBar(count($categories));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $bar->start();

        foreach ($categories as $source) {
            $sourceId = (string) ($source['id'] ?? '');

            if ($sourceId === '') {
                throw new RuntimeException('Category without source id found.');
            }

            $translations = $this->normalizeTranslations($source['translations'] ?? []);
            $name = $this->localizedField($translations, ['name']);

            if ($name === []) {
                throw new RuntimeException("Category {$sourceId} has no translated name.");
            }

            $frName = $name['fr'] ?? reset($name);

            $category = Category::withTrashed()
                ->whereRaw("name->>? = ?", ['fr', $frName])
                ->first() ?? new Category();

            $category->fill([
                'name' => $name,
                'slug' => $this->categorySlugs($name, $category),
                'description' => $this->localizedField($translations, ['description', 'desc']),
                'use' => $this->localizedField($translations, ['use']),
                'price_label' => $this->localizedField($translations, ['price']),
            ]);

            $category->save();

            if ($category->trashed()) {
                $category->restore();
            }

            $map[$sourceId] = $category->id;
            $parents[$sourceId] = $source['parent_id'] ?? null;

            $bar->setMessage($frName);
            $bar->advance();
        }

        foreach ($parents as $sourceId => $parentSourceId) {
            $categoryId = $map[$sourceId];

            Category::whereKey($categoryId)->update([
                'parent_id' => $parentSourceId ? ($map[$parentSourceId] ?? null) : null,
            ]);
        }

        $bar->setMessage('done');
        $bar->finish();
        $this->newLine();

        return $map;
    }

    private function importProducts(array $products, array $categoryMap, string $imagesPath): void
    {
        $bar = $this->output->createProgressBar(count($products));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $bar->start();

        foreach ($products as $source) {
            $sourceCategoryId = (string) ($source['category_id'] ?? '');
            $categoryId = $categoryMap[$sourceCategoryId] ?? null;

            if (! $categoryId) {
                throw new RuntimeException("Unknown category {$sourceCategoryId} for product ".($source['id'] ?? 'unknown').'.');
            }

            $slug = trim((string) ($source['slug'] ?? ''));

            if ($slug === '') {
                throw new RuntimeException('Product without slug found: '.($source['id'] ?? 'unknown'));
            }

            $translations = $this->normalizeTranslations($source['translations'] ?? []);

            $name = $this->localizedField($translations, ['name']);
            $tag = $this->localizedField($translations, ['tag']);
            $description = $this->localizedField($translations, ['description', 'desc']);
            $details = $this->localizedField($translations, ['details']);
            $contents = $this->localizedField($translations, ['contents', 'items']);

            if ($name === [] && isset($source['name'])) {
                $name = ['fr' => $source['name']];
            }

            if ($tag === [] && isset($source['tag'])) {
                $tag = ['fr' => $source['tag']];
            }

            if ($description === [] && isset($source['description'])) {
                $description = ['fr' => $source['description']];
            }

            if ($details === [] && isset($source['details'])) {
                $details = ['fr' => $source['details']];
            }

            if ($contents === [] && isset($source['contents'])) {
                $contents = ['fr' => $source['contents']];
            }

            $translatedSlug = [];

            foreach ($this->locales as $locale) {
                $translatedSlug[$locale] = $slug;
            }

            $product = Product::withTrashed()
                ->whereRaw("slug->>? = ?", ['fr', $slug])
                ->first() ?? new Product();

            $product->fill([
                'category_id' => $categoryId,
                'active' => ($source['status'] ?? 'active') === 'active',
                'featured' => (bool) ($source['featured'] ?? false),
                'is_new' => (bool) ($source['is_new'] ?? false),
                'sku' => $source['sku'] ?? Arr::get($source, 'legacy_demo.sku'),
                'name' => $name,
                'slug' => $translatedSlug,
                'tag' => $tag ?: null,
                'description' => $description ?: null,
                'claims' => $source['claims'] ?? null,
                'details' => $details ?: null,
                'size' => $source['size'] ?? null,
                'measurements' => $source['measurements'] ?? null,
                'options' => $source['options'] ?? null,
                'contents' => $contents ?: null,
                'price' => Arr::get($source, 'price.amount'),
                'price_min' => Arr::get($source, 'price.min'),
                'price_max' => Arr::get($source, 'price.max'),
                'compare_at_price' => Arr::get($source, 'compare_at_price.amount'),
            ]);

            $product->save();

            if ($product->trashed()) {
                $product->restore();
            }

            $this->syncMedia($product, $source['images'] ?? [], $imagesPath);

            $bar->setMessage($slug);
            $bar->advance();
        }

        $bar->setMessage('done');
        $bar->finish();
        $this->newLine();
    }

    private function syncMedia(Product $product, array $images, string $imagesPath): void
    {
        $product->clearMediaCollection('images');

        foreach ($images as $image) {
            $relativePath = $image['relative_path'] ?? null;

            if (! $relativePath && isset($image['source_ref'])) {
                $relativePath = explode('?', $image['source_ref'], 2)[0];
            }

            if (! $relativePath) {
                continue;
            }

            $absolutePath = $this->safeImagePath($imagesPath, $relativePath);

            if (! is_file($absolutePath)) {
                $this->missingImages[] = $relativePath;

                continue;
            }

            $product
                ->addMedia($absolutePath)
                ->preservingOriginal()
                ->withCustomProperties(array_filter([
                    'role' => $image['role'] ?? null,
                    'source_ref' => $image['source_ref'] ?? null,
                ]))
                ->toMediaCollection('images');

            $this->importedImages++;
        }
    }

    private function validateCatalog(array $categories, array $products): void
    {
        $categoryIds = [];

        foreach ($categories as $category) {
            $id = (string) ($category['id'] ?? '');

            if ($id === '') {
                throw new RuntimeException('Category without source id found.');
            }

            if (isset($categoryIds[$id])) {
                throw new RuntimeException("Duplicate category source id: {$id}");
            }

            $categoryIds[$id] = true;
        }

        $slugs = [];

        foreach ($products as $product) {
            $slug = trim((string) ($product['slug'] ?? ''));

            if ($slug === '') {
                throw new RuntimeException('Product without slug found: '.($product['id'] ?? 'unknown'));
            }

            if (isset($slugs[$slug])) {
                throw new RuntimeException("Duplicate product slug in JSON: {$slug}");
            }

            $slugs[$slug] = true;

            $categoryId = (string) ($product['category_id'] ?? '');

            if (! isset($categoryIds[$categoryId])) {
                throw new RuntimeException("Product {$slug} references unknown category {$categoryId}");
            }
        }
    }

    private function categorySlugs(array $names, Category $category): array
    {
        $slugs = [];
        $fallback = $names['fr'] ?? reset($names);

        foreach ($this->locales as $locale) {
            $base = Str::slug((string) ($names[$locale] ?? $fallback));

            if ($base === '') {
                $base = 'category';
            }

            $slug = $base;
            $suffix = 2;

            while (Category::withTrashed()
                ->when($category->exists, fn ($query) => $query->whereKeyNot($category->getKey()))
                ->whereRaw("slug->>? = ?", [$locale, $slug])
                ->exists()) {
                $slug = $base.'-'.$suffix++;
            }

            $slugs[$locale] = $slug;
        }

        return $slugs;
    }

    private function normalizeTranslations(array $translations): array
    {
        $normalized = [];

        foreach ($translations as $locale => $fields) {
            if (is_array($fields)) {
                $normalized[strtolower((string) $locale)] = $fields;
            }
        }

        return $normalized;
    }

    private function localizedField(array $translations, array $keys): array
    {
        $result = [];

        foreach ($this->locales as $locale) {
            $fields = $translations[$locale] ?? [];

            foreach ($keys as $key) {
                if (array_key_exists($key, $fields) && $fields[$key] !== null && $fields[$key] !== '') {
                    $result[$locale] = $fields[$key];

                    break;
                }
            }
        }

        return $result;
    }

    private function safeImagePath(string $root, string $relativePath): string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        if (str_contains($relativePath, '../')) {
            throw new RuntimeException("Invalid image path: {$relativePath}");
        }

        return rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    private function resolvePath(?string $path, string $default): string
    {
        if (! $path) {
            return $default;
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }
}
