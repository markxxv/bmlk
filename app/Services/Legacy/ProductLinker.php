<?php

namespace App\Services\Legacy;

use Closure;
use Illuminate\Database\ConnectionInterface;

/**
 * Links imported (archive) order lines to current catalog products by name
 * and cleans leftover HTML from legacy line names.
 *
 * Only lines without a product_id are touched. Prices, quantities, order
 * totals, updated_at and ratings_enabled are never changed.
 */
class ProductLinker
{
    /** @var array<string, int> */
    public array $stats = [
        'orders_scanned' => 0,
        'orders_updated' => 0,
        'lines_linked' => 0,
        'names_cleaned' => 0,
        'lines_unmatched' => 0,
    ];

    /** @var array<string, int> legacy name => number of unlinked lines */
    public array $unmatched = [];

    /** @var array<int, array{url:string, image:string}|null> */
    private array $resolved = [];

    /**
     * @param  array<string, int>  $map  lowercase legacy name => product id
     * @param  Closure(int): ?array{url:string, image:string}  $resolve
     */
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly array $map,
        private readonly Closure $resolve,
        private readonly bool $dryRun = false,
    ) {
    }

    public function count(): int
    {
        return $this->db->table('orders')->count();
    }

    /** @param callable():void $tick */
    public function run(callable $tick): void
    {
        $this->db->table('orders')->select('id', 'items')->chunkById(200, function ($orders) use ($tick): void {
            foreach ($orders as $order) {
                $tick();
                $this->stats['orders_scanned']++;
                $this->processOrder($order);
            }
        });
    }

    public static function cleanName(string $name): string
    {
        $name = preg_replace('#<span>\s*-\s*</span>#iu', ' - ', $name);
        $name = strip_tags($name);
        $name = str_replace("\u{2060}", '', $name);

        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    /** Key used in the map file: name without pack suffix, lowercase. */
    public static function key(string $name): string
    {
        $name = preg_replace('#<span>.*$#isu', '', $name);
        $name = str_replace(["\u{2060}", '’'], ['', "'"], $name);

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name)));
    }

    private function processOrder(object $order): void
    {
        $items = json_decode((string) $order->items, true);
        if (! is_array($items)) {
            return;
        }

        $changed = false;
        foreach ($items as $i => $item) {
            if (! is_array($item) || (int) ($item['product_id'] ?? 0) > 0) {
                continue;
            }

            $name = (string) ($item['name'] ?? '');
            $clean = self::cleanName($name);
            if ($clean !== $name) {
                $items[$i]['name'] = $clean;
                $this->stats['names_cleaned']++;
                $changed = true;
            }

            $productId = $this->map[self::key($name)] ?? null;
            $product = $productId ? $this->resolveProduct($productId) : null;

            if ($product === null) {
                $this->stats['lines_unmatched']++;
                $this->unmatched[$clean] = ($this->unmatched[$clean] ?? 0) + 1;

                continue;
            }

            $items[$i]['product_id'] = $productId;
            $items[$i]['url'] = $product['url'];
            $items[$i]['image'] = $product['image'];
            $this->stats['lines_linked']++;
            $changed = true;
        }

        if (! $changed) {
            return;
        }

        $this->stats['orders_updated']++;
        if (! $this->dryRun) {
            $this->db->table('orders')->where('id', $order->id)->update([
                'items' => json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        }
    }

    private function resolveProduct(int $id): ?array
    {
        if (! array_key_exists($id, $this->resolved)) {
            $this->resolved[$id] = ($this->resolve)($id);
        }

        return $this->resolved[$id];
    }
}
