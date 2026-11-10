<?php

namespace Database\Seeders;

use App\Models\Representative;
use Illuminate\Database\Seeder;

class RepresentativeSeeder extends Seeder
{
    public function run(): void
    {
        $representatives = [
            [
                'name' => 'BLACK MILK France',
                'country' => ['fr' => 'FRANCE', 'en' => 'FRANCE', 'es' => 'FRANCIA'],
                'tag' => ['fr' => 'Siège', 'en' => 'HQ', 'es' => 'Sede'],
                'city' => ['fr' => 'Paris', 'en' => 'Paris', 'es' => 'París'],
                'cta' => ['fr' => 'Commander', 'en' => 'Order', 'es' => 'Pedir'],
                'url' => null,
                'instagram' => null,
            ],
            [
                'name' => 'NailsLo',
                'country' => ['fr' => 'FRANCE', 'en' => 'FRANCE', 'es' => 'FRANCIA'],
                'tag' => ['fr' => 'Revendeur', 'en' => 'Reseller', 'es' => 'Distribuidor'],
                'city' => ['fr' => 'En ligne & point de vente', 'en' => 'Online & in-store', 'es' => 'Online y en tienda'],
                'cta' => ['fr' => 'Boutique', 'en' => 'Shop', 'es' => 'Tienda'],
                'url' => 'https://www.nailslo.com/',
                'instagram' => null,
            ],
            [
                'name' => 'Ayan Academy',
                'country' => ['fr' => 'REIMS', 'en' => 'REIMS', 'es' => 'REIMS'],
                'tag' => ['fr' => 'Point de vente', 'en' => 'Store', 'es' => 'Punto de venta'],
                'city' => ['fr' => 'Reims — achat sur place', 'en' => 'Reims — buy in person', 'es' => 'Reims — compra en persona'],
                'cta' => ['fr' => 'Instagram', 'en' => 'Instagram', 'es' => 'Instagram'],
                'url' => 'https://www.instagram.com/ayanacademy.fr',
                'instagram' => 'https://www.instagram.com/ayanacademy.fr',
            ],
            [
                'name' => 'Crystal Nails Greece',
                'country' => ['fr' => 'GRÈCE', 'en' => 'GREECE', 'es' => 'GRECIA'],
                'tag' => ['fr' => 'Officiel', 'en' => 'Official', 'es' => 'Oficial'],
                'city' => ['fr' => 'Athènes', 'en' => 'Athens', 'es' => 'Atenas'],
                'cta' => ['fr' => 'Boutique', 'en' => 'Shop', 'es' => 'Tienda'],
                'url' => 'https://www.crystalnailsgreece.gr/',
                'instagram' => 'https://www.instagram.com/crystalnailsgreece',
            ],
            [
                'name' => 'Ioana Cristescu',
                'country' => ['fr' => 'ESPAGNE', 'en' => 'SPAIN', 'es' => 'ESPAÑA'],
                'tag' => ['fr' => 'Officiel', 'en' => 'Official', 'es' => 'Oficial'],
                'city' => ['fr' => 'Boutique en ligne', 'en' => 'Online shop', 'es' => 'Tienda online'],
                'cta' => ['fr' => 'Boutique', 'en' => 'Shop', 'es' => 'Tienda'],
                'url' => 'https://ioanacristescu.com/',
                'instagram' => 'https://www.instagram.com/ioanacristescunailartist',
            ],
            [
                'name' => 'Rosi Cangueiro',
                'country' => ['fr' => 'PORTUGAL', 'en' => 'PORTUGAL', 'es' => 'PORTUGAL'],
                'tag' => ['fr' => 'Officiel', 'en' => 'Official', 'es' => 'Oficial'],
                'city' => ['fr' => 'Gamme complète', 'en' => 'Full range', 'es' => 'Gama completa'],
                'cta' => ['fr' => 'Boutique', 'en' => 'Shop', 'es' => 'Tienda'],
                'url' => 'https://launch-wish-app.lovable.app/',
                'instagram' => 'https://www.instagram.com/rosicangueiro.fbhub',
            ],
            [
                'name' => 'Jessica Neves',
                'country' => ['fr' => 'PORTUGAL', 'en' => 'PORTUGAL', 'es' => 'PORTUGAL'],
                'tag' => ['fr' => 'Hémostatique', 'en' => 'Hemostatic', 'es' => 'Hemostático'],
                'city' => ['fr' => 'Hémostatique uniquement', 'en' => 'Hemostatic only', 'es' => 'Solo hemostático'],
                'cta' => ['fr' => 'Boutique', 'en' => 'Shop', 'es' => 'Tienda'],
                'url' => 'https://bio.site/nevescjessica',
                'instagram' => null,
            ],
            [
                'name' => 'Glamour Nails CR',
                'country' => ['fr' => 'COSTA RICA', 'en' => 'COSTA RICA', 'es' => 'COSTA RICA'],
                'tag' => ['fr' => 'Officiel', 'en' => 'Official', 'es' => 'Oficial'],
                'city' => ['fr' => 'Costa Rica', 'en' => 'Costa Rica', 'es' => 'Costa Rica'],
                'cta' => ['fr' => 'Instagram', 'en' => 'Instagram', 'es' => 'Instagram'],
                'url' => 'https://www.instagram.com/glamournailscr',
                'instagram' => 'https://www.instagram.com/glamournailscr',
            ],
            [
                'name' => 'Mixcoco',
                'country' => ['fr' => 'COLOMBIE', 'en' => 'COLOMBIA', 'es' => 'COLOMBIA'],
                'tag' => ['fr' => 'Officiel', 'en' => 'Official', 'es' => 'Oficial'],
                'city' => ['fr' => 'Bucaramanga, Santander', 'en' => 'Bucaramanga, Santander', 'es' => 'Bucaramanga, Santander'],
                'cta' => ['fr' => 'Boutique', 'en' => 'Shop', 'es' => 'Tienda'],
                'url' => 'https://linktr.ee/mixcoco.col',
                'instagram' => null,
            ],
            [
                'name' => 'Pro Beauty Supply',
                'country' => ['fr' => 'USA · MIAMI', 'en' => 'USA · MIAMI', 'es' => 'EE. UU. · MIAMI'],
                'tag' => ['fr' => 'Officiel', 'en' => 'Official', 'es' => 'Oficial'],
                'city' => ['fr' => 'Miami, USA', 'en' => 'Miami, USA', 'es' => 'Miami, EE. UU.'],
                'cta' => ['fr' => 'Instagram', 'en' => 'Instagram', 'es' => 'Instagram'],
                'url' => 'https://www.instagram.com/pro.beautysupply',
                'instagram' => 'https://www.instagram.com/pro.beautysupply',
            ],
            [
                'name' => 'Smirnova Distribution',
                'country' => ['fr' => 'ROUMANIE', 'en' => 'ROMANIA', 'es' => 'RUMANÍA'],
                'tag' => ['fr' => 'Officiel', 'en' => 'Official', 'es' => 'Oficial'],
                'city' => ['fr' => 'București', 'en' => 'Bucharest', 'es' => 'Bucarest'],
                'cta' => ['fr' => 'Nous contacter', 'en' => 'Contact us', 'es' => 'Contáctanos'],
                'url' => null,
                'instagram' => null,
            ],
        ];

        foreach ($representatives as $index => $data) {
            Representative::query()->updateOrCreate(
                ['name' => $data['name']],
                [
                    ...$data,
                    'active' => true,
                    'sort' => $index + 1,
                ],
            );
        }
    }
}
