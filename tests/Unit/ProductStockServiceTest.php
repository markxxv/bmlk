<?php

namespace Tests\Unit;

use App\Services\ProductStockService;
use PHPUnit\Framework\TestCase;

class ProductStockServiceTest extends TestCase
{
    public function test_invalid_and_missing_items_are_ignored_safely(): void
    {
        $service = new ProductStockService;

        $this->assertSame([], $service->quantitiesForItems(null));
        $this->assertSame([], $service->quantitiesForItems('invalid'));
        $this->assertSame([
            1 => 5,
            2 => 1,
        ], $service->quantitiesForItems([
            null,
            'invalid',
            [],
            ['product_id' => [], 'quantity' => 1],
            ['product_id' => 1, 'quantity' => []],
            ['product_id' => 1, 'quantity' => null],
            ['product_id' => 1, 'quantity' => -5],
            ['product_id' => 1, 'quantity' => 0],
            ['product_id' => 1, 'quantity' => 2.5],
            ['product_id' => 1, 'quantity' => true],
            ['product_id' => true, 'quantity' => 1],
            ['product_id' => 0, 'quantity' => 1],
            ['product_id' => 2, 'quantity' => '1'],
            ['product_id' => 1, 'quantity' => 2],
            ['product_id' => '1', 'quantity' => '3'],
        ]));
    }

    public function test_aggregation_cannot_overflow_the_stock_column_range(): void
    {
        $service = new ProductStockService;

        $this->assertSame([1 => 2_147_483_647], $service->quantitiesForItems([
            ['product_id' => 1, 'quantity' => 2_147_483_647],
            ['product_id' => 1, 'quantity' => 20],
        ]));
    }
}
