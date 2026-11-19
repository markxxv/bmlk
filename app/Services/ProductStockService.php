<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProductStockService
{
    private const MAX_STOCK = 2_147_483_647;

    /**
     * Call inside the transaction that successfully marks an order paid.
     * NULL means stock is not tracked. Zero is informational, not a sales restriction.
     */
    public function decrementForItems(mixed $items): void
    {
        foreach ($this->quantitiesForItems($items) as $productId => $quantity) {
            // An atomic update prevents concurrent paid orders from losing deductions.
            // Update existing catalog records even if temporarily hidden or soft-deleted.
            DB::update(
                'UPDATE products
                 SET stock = CASE WHEN stock <= ? THEN 0 ELSE stock - ? END,
                     updated_at = ?
                 WHERE id = ? AND stock IS NOT NULL AND stock <> 0',
                [$quantity, $quantity, now(), $productId],
            );
        }
    }

    /**
     * Aggregate snapshot line quantities by product, also for future stock validation.
     * Ignore malformed or missing legacy item data without coercion or warnings.
     *
     * @return array<int, int>
     */
    public function quantitiesForItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $quantities = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $productId = $this->positiveInteger($item['product_id'] ?? null);
            $quantity = $this->positiveInteger($item['quantity'] ?? null);

            if ($productId === null || $quantity === null) {
                continue;
            }

            $current = $quantities[$productId] ?? 0;
            $quantities[$productId] = $current + min($quantity, self::MAX_STOCK - $current);
        }

        // Consistent locking order when multiple orders contain the same products.
        ksort($quantities, SORT_NUMERIC);

        return $quantities;
    }

    private function positiveInteger(mixed $value): ?int
    {
        if (! is_int($value) && ! is_string($value)) {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $integer === false ? null : $integer;
    }
}
