<?php

namespace Database\Seeders;

use App\Models\Delivery;
use Illuminate\Database\Seeder;

class DeliverySeeder extends Seeder
{
    public function run(): void
    {
        $deliveries = [
            [
                'code' => 'fr',
                'name' => 'France',
                'carrier' => 'Colissimo',
                'delay' => '2 à 4 jours ouvrés',
                'price' => 6.90,
                'free_from' => 100,
                'sort' => 1,
            ],
            [
                'code' => 'eu1',
                'name' => 'UE proche',
                'carrier' => 'Colissimo Europe',
                'delay' => '3 à 7 jours ouvrés',
                'price' => 12.90,
                'free_from' => 100,
                'sort' => 2,
            ],
            [
                'code' => 'eu2',
                'name' => 'UE Est / Nord',
                'carrier' => 'UPS Standard',
                'delay' => '5 à 8 jours ouvrés',
                'price' => 17.90,
                'free_from' => 100,
                'sort' => 3,
            ],
            [
                'code' => 'int',
                'name' => 'Hors UE',
                'carrier' => 'DHL Express',
                'delay' => '5 à 10 jours ouvrés',
                'price' => 46.00,
                'free_from' => null,
                'sort' => 4,
            ],
        ];

        foreach ($deliveries as $data) {
            Delivery::query()->updateOrCreate(
                ['code' => $data['code']],
                [...$data, 'active' => true],
            );
        }
    }
}
