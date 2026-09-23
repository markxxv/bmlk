<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        Event::query()->delete();

        $events = [
            [
                'name' => [
                    'fr' => 'Formation BLACK MILK — Paris',
                    'en' => 'BLACK MILK Training — Paris',
                    'ro' => 'Curs BLACK MILK — Paris',
                ],
                'type' => 'training',
                'description' => [
                    'fr' => "Deux jours de formation consacrés aux techniques BLACK MILK, à la précision du geste et au travail professionnel en cabine.",
                    'en' => 'Two days of training focused on BLACK MILK techniques, precision and professional salon practice.',
                    'ro' => 'Două zile de formare dedicate tehnicilor BLACK MILK, preciziei și lucrului profesional în salon.',
                ],
                'starts_at' => '2027-01-23 00:00:00',
                'ends_at' => '2027-01-24 23:59:59',
                'country' => 'France',
                'city' => 'Paris',
            ],
            [
                'name' => [
                    'fr' => 'Formation instructeur BLACK MILK — Lisbonne',
                    'en' => 'BLACK MILK Instructor Training — Lisbon',
                    'ro' => 'Formare instructor BLACK MILK — Lisabona',
                ],
                'type' => 'training',
                'description' => [
                    'fr' => 'Quatre jours de formation intensive pour maîtriser la méthode BLACK MILK et apprendre à la transmettre avec précision.',
                    'en' => 'Four days of intensive training to master the BLACK MILK method and learn how to teach it with precision.',
                    'ro' => 'Patru zile de formare intensivă pentru aprofundarea metodei BLACK MILK și transmiterea ei cu precizie.',
                ],
                'starts_at' => '2027-03-11 00:00:00',
                'ends_at' => '2027-03-14 23:59:59',
                'country' => 'Portugal',
                'city' => 'Lisbon',
            ],
            [
                'name' => [
                    'fr' => 'Formation BLACK MILK — Paris',
                    'en' => 'BLACK MILK Training — Paris',
                    'ro' => 'Curs BLACK MILK — Paris',
                ],
                'type' => 'training',
                'description' => [
                    'fr' => 'Deux jours de perfectionnement autour des techniques BLACK MILK, de la précision et de la maîtrise du résultat.',
                    'en' => 'Two days of advanced training focused on BLACK MILK techniques, precision and consistent results.',
                    'ro' => 'Două zile de perfecționare dedicate tehnicilor BLACK MILK, preciziei și controlului rezultatului.',
                ],
                'starts_at' => '2027-04-10 00:00:00',
                'ends_at' => '2027-04-11 23:59:59',
                'country' => 'France',
                'city' => 'Paris',
            ],
        ];

        foreach ($events as $index => $data) {
            Event::query()->create([
                ...$data,
                'url' => null,
                'address' => null,
                'price' => null,
                'ticket_url' => null,
                'active' => true,
                'sort' => $index + 1,
            ]);
        }
    }
}
