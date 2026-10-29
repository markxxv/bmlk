<?php

namespace Database\Seeders;

use App\Models\ShippingBox;
use Illuminate\Database\Seeder;

class ShippingBoxSeeder extends Seeder
{
    public function run(): void
    {
        // Templates only: the warehouse must measure its actual cardboard boxes.
        // No fabricated dimensions or weights are enabled for paid shipping quotes.
        foreach ([
            ['code' => 'box-s', 'name' => 'Petit carton', 'sort' => 10],
            ['code' => 'box-m', 'name' => 'Carton moyen', 'sort' => 20],
            ['code' => 'box-l', 'name' => 'Grand carton', 'sort' => 30],
            ['code' => 'box-xl', 'name' => 'Très grand carton', 'sort' => 40],
        ] as $box) {
            ShippingBox::query()->firstOrCreate(
                ['code' => $box['code']],
                [...$box, 'active' => false],
            );
        }
    }
}
