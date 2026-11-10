<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Legacy\ProductLinker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkLegacyProducts extends Command
{
    protected $signature = 'legacy:link-products
        {--dry-run : Count only, write nothing}
        {--map=database/data/legacy-product-map.json : Map file (legacy name => product id)}';

    protected $description = 'Link imported archive order lines to current products by name (adds product_id, url, image) and clean legacy HTML in names.';

    public function handle(): int
    {
        $path = base_path($this->option('map'));
        if (! is_file($path)) {
            $this->components->error("Map file not found: {$path}");

            return self::FAILURE;
        }

        $map = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $dry = (bool) $this->option('dry-run');

        $resolve = function (int $id): ?array {
            $product = Product::query()->with('media')->find($id);
            if ($product === null) {
                return null;
            }
            $slug = $product->getTranslation('slug', 'fr', false) ?: $product->getTranslation('slug', 'en', false);

            return [
                'url' => $slug ? route('shop.product', ['slug' => $slug]) : route('shop.index'),
                'image' => $product->storefrontImageUrl('thumb'),
            ];
        };

        $linker = new ProductLinker(DB::connection(), $map, $resolve, $dry);

        $this->components->info($dry ? 'DRY RUN: nothing will be written.' : 'Linking archive order lines to products.');
        $bar = $this->output->createProgressBar($linker->count());
        $bar->setFormat(' Orders %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();
        $linker->run(fn () => $bar->advance());
        $bar->finish();
        $this->newLine(2);

        $this->table(['Result', 'Count'], collect($linker->stats)->map(fn ($v, $k) => [$k, $v])->values()->all());

        if ($linker->unmatched !== []) {
            arsort($linker->unmatched);
            $this->components->info('Lines left without a product (top 15):');
            $this->table(['Name', 'Lines'], collect($linker->unmatched)->take(15)->map(fn ($v, $k) => [$k, $v])->values()->all());
        }

        return self::SUCCESS;
    }
}
