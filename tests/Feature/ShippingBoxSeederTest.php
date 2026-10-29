<?php

namespace Tests\Feature;

use App\Models\ShippingBox;
use Database\Seeders\ShippingBoxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingBoxSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_empty_templates_receive_dimensions_and_become_available(): void
    {
        ShippingBox::query()->create([
            'code' => 'box-s',
            'name' => 'Petit carton',
            'sort' => 10,
            'active' => false,
        ]);

        $this->seed(ShippingBoxSeeder::class);

        $box = ShippingBox::query()->where('code', 'box-s')->firstOrFail();

        $this->assertTrue($box->active);
        $this->assertSame('20.00', $box->inner_length_cm);
        $this->assertSame('21.00', $box->outer_length_cm);
        $this->assertSame(110, $box->tare_weight_g);
        $this->assertCount(4, ShippingBox::query()->get());
        $this->assertCount(4, ShippingBox::ready()->get());
    }

    public function test_reseeding_does_not_overwrite_merchant_measurements_or_disabled_boxes(): void
    {
        $this->seed(ShippingBoxSeeder::class);

        $box = ShippingBox::query()->where('code', 'box-s')->firstOrFail();
        $box->update([
            'name' => 'Carton mesuré',
            'inner_length_cm' => 23.5,
            'outer_length_cm' => 24.5,
            'tare_weight_g' => 133,
            'active' => false,
        ]);

        $this->seed(ShippingBoxSeeder::class);

        $box->refresh();
        $this->assertSame('Carton mesuré', $box->name);
        $this->assertSame('23.50', $box->inner_length_cm);
        $this->assertSame('24.50', $box->outer_length_cm);
        $this->assertSame(133, $box->tare_weight_g);
        $this->assertFalse($box->active);
        $this->assertCount(4, ShippingBox::query()->get());
    }
}
