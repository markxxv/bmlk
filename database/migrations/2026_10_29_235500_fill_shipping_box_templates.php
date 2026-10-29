<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shipping_boxes')) {
            return;
        }

        // The original seeder left four empty, disabled templates. Provide
        // provisional sizes now, without touching boxes already edited by staff.
        // Replace these estimates with physical measurements in Filament.
        $boxes = [
            'box-s' => [
                'name' => 'Petit carton (dimensions estimées)',
                'inner_length_cm' => 20, 'inner_width_cm' => 14, 'inner_height_cm' => 8,
                'outer_length_cm' => 21, 'outer_width_cm' => 15, 'outer_height_cm' => 9,
                'tare_weight_g' => 110,
            ],
            'box-m' => [
                'name' => 'Carton moyen (dimensions estimées)',
                'inner_length_cm' => 28, 'inner_width_cm' => 20, 'inner_height_cm' => 12,
                'outer_length_cm' => 29, 'outer_width_cm' => 21, 'outer_height_cm' => 13,
                'tare_weight_g' => 220,
            ],
            'box-l' => [
                'name' => 'Grand carton (dimensions estimées)',
                'inner_length_cm' => 36, 'inner_width_cm' => 28, 'inner_height_cm' => 17,
                'outer_length_cm' => 37, 'outer_width_cm' => 29, 'outer_height_cm' => 18,
                'tare_weight_g' => 380,
            ],
            'box-xl' => [
                'name' => 'Très grand carton (dimensions estimées)',
                'inner_length_cm' => 45, 'inner_width_cm' => 35, 'inner_height_cm' => 25,
                'outer_length_cm' => 46, 'outer_width_cm' => 36, 'outer_height_cm' => 26,
                'tare_weight_g' => 650,
            ],
        ];

        foreach ($boxes as $code => $values) {
            $query = DB::table('shipping_boxes')->where('code', $code);
            foreach ([
                'inner_length_cm', 'inner_width_cm', 'inner_height_cm',
                'outer_length_cm', 'outer_width_cm', 'outer_height_cm', 'tare_weight_g',
            ] as $column) {
                $query->whereNull($column);
            }

            $query->update([
                ...$values,
                'active' => true,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Do not erase packaging dimensions that staff may have verified or changed.
    }
};
