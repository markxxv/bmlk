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
                'country' => ['fr' => 'FRANCE', 'en' => 'FRANCE', 'ro' => 'FRANȚA'],
                'tag' => ['fr' => 'Siège', 'en' => 'HQ', 'ro' => 'Sediu'],
                'city' => ['fr' => 'Paris', 'en' => 'Paris', 'ro' => 'Paris'],
                'cta' => ['fr' => 'Commander', 'en' => 'Order', 'ro' => 'Comandă'],
                'url' => null,
                'instagram' => null,
            ],
            [
                'name' => 'NailsLo',
                'country' => ['fr' => 'FRANCE', 'en' => 'FRANCE', 'ro' => 'FRANȚA'],
                'tag' => ['fr' => 'Revendeur', 'en' => 'Reseller', 'ro' => 'Distribuitor'],
                'city' => ['fr' => 'En ligne & point de vente', 'en' => 'Online & in-store', 'ro' => 'Online & în magazin'],
                'cta' => ['fr' => 'Boutique', 'en' => 'Shop', 'ro' => 'Magazin'],
                'url' => 'https://www.nailslo.com/',
                'instagram' => null,
            ],
            [
                'name' => 'Ayan Academy',
                'country' => ['fr' => 'REIMS', 'en' => 'REIMS', 'ro' => 'REIMS'],
                'tag' => ['fr' => 'Point de vente', 'en' => 'Store', 'ro' => 'Punct de vânzare'],
                'city' => ['fr' => 'Reims — achat sur place', 'en' => 'Reims — buy in person', 'ro' => 'Reims — cumpără pe loc'],
                'cta' => ['fr' => 'Instagram', 'en' => 'Instagram', 'ro' => 'Instagram'],
                'url' => 'https://www.instagram.com/ayanacademy.fr',
                'instagram' => 'https://www.instagram.com/ayanacademy.fr',
            ],
            [
                'name' => 'Crystal Nails Greece',
                'country' => ['fr' => 'GRÈCE', 'en' => 'GREECE', 'ro' => 'GRECIA'],
                'tag' => ['fr' => 'Officiel', 'en' => 'Official', 'ro' => 'Oficial'],
                'city' => ['fr' => 'Athènes', 'en' => 'Athens', 'ro' => 'Atena'],
                'cta' => ['fr' => 'Boutique', 'en' => 'Shop', 'ro' => 'Magazin'],
                'url' => 'https://www.crystalnailsgreece.gr/',
                'instagram' => 'https://www.instagram.com/crystalnailsgreece',
            ],
            [
                'name' => 'Ioana Cristescu',
                'country' => ['fr' => 'ESPAGNE', 'en' => 'SPAIN', 'ro' => 'SPANIA'],
                'tag' => ['fr' => 'Officiel', 'en' => 'Official', 'ro' => 'Oficial'],
                'city' => ['fr' => 'Boutique en ligne', 'en' => 'Online shop', 'ro' => 'Magazin online'],
                'cta' => ['fr' => 'Boutique', 'en' => 'Shop', 'ro' => 'Magazin'],
                'url' => 'https://ioanacristescu.com/',
                'instagram' => 'https://www.instagram.com/ioanacristescunailartist',
            ],
            [
                'name' => 'Rosi Cangueiro',
                'country' => ['fr' => 'PORTUGAL', 'en' => 'PORTUGAL', 'ro' => 'PORTUGALIA'],
                'tag' => ['fr' => 'Officiel', 'en' => 'Official', 'ro' => 'Oficial'],
                'city' => ['fr' => 'Gamme complète', 'en' => 'Full range', 'ro' => 'Gamă completă'],
                'cta' => ['fr' => 'Boutique', 'en' => 'Shop', 'ro' => 'Magazin'],
                'url' => 'https://launch-wish-app.lovable.app/',
                'instagram' => 'https://www.instagram.com/rosicangueiro.fbhub',
            ],
            [
                'name' => 'Jessica Neves',
                'country' => ['fr' => 'PORTUGAL', 'en' => 'PORTUGAL', 'ro' => 'PORTUGALIA'],
                'tag' => ['fr' => 'Hémostatique', 'en' => 'Hemostatic', 'ro' => 'Hemostatic'],
                'city' => ['fr' => 'Hémostatique uniquement', 'en' => 'Hemostatic only', 'ro' => 'Doar hemostatic'],
                'cta' => ['fr' => 'Boutique', 'en' => 'Shop', 'ro' => 'Magazin'],
                'url' => 'https://bio.site/nevescjessica',
                'instagram' => null,
            ],
            [
                'name' => 'Glamour Nails CR',
                'country' => ['fr' => 'COSTA RICA', 'en' => 'COSTA RICA', 'ro' => 'COSTA RICA'],
                'tag' => ['fr' => 'Officiel', 'en' => 'Official', 'ro' => 'Oficial'],
                'city' => ['fr' => 'Costa Rica', 'en' => 'Costa Rica', 'ro' => 'Costa Rica'],
                'cta' => ['fr' => 'Instagram', 'en' => 'Instagram', 'ro' => 'Instagram'],
                'url' => 'https://www.instagram.com/glamournailscr',
                'instagram' => 'https://www.instagram.com/glamournailscr',
            ],
            [
                'name' => 'Mixcoco',
                'country' => ['fr' => 'COLOMBIE', 'en' => 'COLOMBIA', 'ro' => 'COLUMBIA'],
                'tag' => ['fr' => 'Officiel', 'en' => 'Official', 'ro' => 'Oficial'],
                'city' => ['fr' => 'Bucaramanga, Santander', 'en' => 'Bucaramanga, Santander', 'ro' => 'Bucaramanga, Santander'],
                'cta' => ['fr' => 'Boutique', 'en' => 'Shop', 'ro' => 'Magazin'],
                'url' => 'https://linktr.ee/mixcoco.col',
                'instagram' => null,
            ],
            [
                'name' => 'Pro Beauty Supply',
                'country' => ['fr' => 'USA · MIAMI', 'en' => 'USA · MIAMI', 'ro' => 'SUA · MIAMI'],
                'tag' => ['fr' => 'Officiel', 'en' => 'Official', 'ro' => 'Oficial'],
                'city' => ['fr' => 'Miami, USA', 'en' => 'Miami, USA', 'ro' => 'Miami, SUA'],
                'cta' => ['fr' => 'Instagram', 'en' => 'Instagram', 'ro' => 'Instagram'],
                'url' => 'https://www.instagram.com/pro.beautysupply',
                'instagram' => 'https://www.instagram.com/pro.beautysupply',
            ],
            [
                'name' => 'Smirnova Distribution',
                'country' => ['fr' => 'ROUMANIE', 'en' => 'ROMANIA', 'ro' => 'ROMÂNIA'],
                'tag' => ['fr' => 'Officiel', 'en' => 'Official', 'ro' => 'Oficial'],
                'city' => ['fr' => 'București', 'en' => 'Bucharest', 'ro' => 'București'],
                'cta' => ['fr' => 'Nous contacter', 'en' => 'Contact us', 'ro' => 'Contactează-ne'],
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
