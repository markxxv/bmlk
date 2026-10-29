<?php

namespace Database\Seeders;

use App\Models\ShippingBox;
use Illuminate\Database\Seeder;

class ShippingBoxSeeder extends Seeder
{
    public function run(): void
    {
        // Provisional box sizes/weights for initial Sendcloud quotes. These are
        // estimates, NOT measurements of the merchant's actual packaging.
        // The merchant must replace them with measured values in Filament.
        $boxes = [
            [
                'code' => 'box-s', 'name' => 'Petit carton (dimensions estimées)', 'sort' => 10,
                'inner_length_cm' => 20, 'inner_width_cm' => 14, 'inner_height_cm' => 8,
                'outer_length_cm' => 21, 'outer_width_cm' => 15, 'outer_height_cm' => 9,
                'tare_weight_g' => 110,
            ],
            [
                'code' => 'box-m', 'name' => 'Carton moyen (dimensions estimées)', 'sort' => 20,
                'inner_length_cm' => 28, 'inner_width_cm' => 20, 'inner_height_cm' => 12,
                'outer_length_cm' => 29, 'outer_width_cm' => 21, 'outer_height_cm' => 13,
                'tare_weight_g' => 220,
            ],
            [
                'code' => 'box-l', 'name' => 'Grand carton (dimensions estimées)', 'sort' => 30,
                'inner_length_cm' => 36, 'inner_width_cm' => 28, 'inner_height_cm' => 17,
                'outer_length_cm' => 37, 'outer_width_cm' => 29, 'outer_height_cm' => 18,
                'tare_weight_g' => 380,
            ],
            [
                'code' => 'box-xl', 'name' => 'Très grand carton (dimensions estimées)', 'sort' => 40,
                'inner_length_cm' => 45, 'inner_width_cm' => 35, 'inner_height_cm' => 25,
                'outer_length_cm' => 46, 'outer_width_cm' => 36, 'outer_height_cm' => 26,
                'tare_weight_g' => 650,
            ],
        ];

        foreach ($boxes as $data) {
            $box = ShippingBox::query()->firstOrNew(['code' => $data['code']]);

            if ($box->exists) {
                // Populate only completely empty templates from the original seeder.
                // Never overwrite user-managed dimensions, weights or disabled boxes.
                $empty = collect([
                    'inner_length_cm', 'inner_width_cm', 'inner_height_cm',
                    'outer_length_cm', 'outer_width_cm', 'outer_height_cm',
                    'tare_weight_g',
                ])->every(fn (string $field): bool => $box->{$field} === null);

                if (! $empty) {
                    continue;
                }
            }

            $box->fill([...$data, 'active' => true])->save();
        }
    }
}
