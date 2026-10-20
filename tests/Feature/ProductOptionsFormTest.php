<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\ProductResource;
use Tests\TestCase;

class ProductOptionsFormTest extends TestCase
{
    public function test_edit_form_drops_empty_legacy_options_and_keeps_real_options(): void
    {
        $options = [
            [
                'id' => 'diameter',
                'values' => ['0,21 mm', '0,23 mm'],
            ],
            [
                'id' => 'pack_quantity',
                'values' => [
                    ['quantity' => 1, 'price' => ['currency' => 'EUR', 'amount' => 5]],
                    ['quantity' => 5, 'price' => ['currency' => 'EUR', 'amount' => 20]],
                ],
            ],
            ['id' => null, 'values' => []],
            ['values' => []],
            ['id' => 'diameter', 'values' => ['', null]],
            ['id' => 'pack_quantity', 'values' => [['quantity' => null, 'price' => ['amount' => null]]]],
            ['id' => 'unknown', 'values' => ['something']],
        ];

        $result = ProductResource::optionsForForm($options);

        $this->assertCount(2, $result);
        $this->assertSame('diameter', $result[0]['type']);
        $this->assertCount(2, $result[0]['values']);
        $this->assertSame('pack_quantity', $result[1]['type']);
        $this->assertSame(1, $result[1]['values'][0]['quantity']);
        $this->assertSame(5, $result[1]['values'][0]['price']);
        $this->assertSame(5, $result[1]['values'][1]['quantity']);
        $this->assertSame(20, $result[1]['values'][1]['price']);
    }
}
