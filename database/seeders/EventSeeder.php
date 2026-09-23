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
                    'fr' => 'Formation Black Milk — Paris',
                    'en' => 'Black Milk Training — Paris',
                    'ro' => 'Curs Black Milk — Paris',
                ],
                'type' => 'training',
                'description' => [
                    'fr' => "Deux jours d'immersion dans l'univers et les techniques Black Milk.",
                    'en' => 'Two days of immersion in the Black Milk universe and techniques.',
                    'ro' => 'Două zile de imersiune în universul și tehnicile Black Milk.',
                ],
                'starts_at' => '2027-01-23 00:00:00',
                'ends_at' => '2027-01-24 23:59:59',
                'country' => 'France',
                'city' => 'Paris',
            ],
            [
                'name' => [
                    'fr' => 'Workshops Black Milk',
                    'en' => 'Black Milk Workshops',
                    'ro' => 'Workshopuri Black Milk',
                ],
                'type' => 'workshop',
                'description' => [
                    'fr' => 'De nouvelles dates de workshops seront dévoilées prochainement.',
                    'en' => 'New workshop dates will be announced soon.',
                    'ro' => 'Noi date pentru workshopuri vor fi anunțate în curând.',
                ],
                'country' => 'International',
            ],
            [
                'name' => [
                    'fr' => 'Formation Black Milk — Amérique',
                    'en' => 'Black Milk Training — America',
                    'ro' => 'Curs Black Milk — America',
                ],
                'type' => 'international_tour',
                'description' => [
                    'fr' => 'Black Milk arrive en Amérique. La ville et les dates seront annoncées prochainement.',
                    'en' => 'Black Milk is coming to America. The city and dates will be announced soon.',
                    'ro' => 'Black Milk ajunge în America. Orașul și datele vor fi anunțate în curând.',
                ],
                'country' => 'Amérique',
            ],
            [
                'name' => [
                    'fr' => 'Workshops Black Milk',
                    'en' => 'Black Milk Workshops',
                    'ro' => 'Workshopuri Black Milk',
                ],
                'type' => 'workshop',
                'description' => [
                    'fr' => 'De nouvelles dates de workshops seront dévoilées prochainement.',
                    'en' => 'New workshop dates will be announced soon.',
                    'ro' => 'Noi date pentru workshopuri vor fi anunțate în curând.',
                ],
                'country' => 'International',
            ],
            [
                'name' => [
                    'fr' => 'Formation instructeur Black Milk — Lisbonne',
                    'en' => 'Black Milk Instructor Training — Lisbon',
                    'ro' => 'Formare instructor Black Milk — Lisabona',
                ],
                'type' => 'training',
                'description' => [
                    'fr' => 'Quatre jours de formation intensive pour maîtriser la méthode Black Milk et apprendre à la transmettre avec précision.',
                    'en' => 'Four days of intensive training to master the Black Milk method and learn how to teach it with precision.',
                    'ro' => 'Patru zile de formare intensivă pentru aprofundarea metodei Black Milk și transmiterea ei cu precizie.',
                ],
                'starts_at' => '2027-03-11 00:00:00',
                'ends_at' => '2027-03-14 23:59:59',
                'country' => 'Portugal',
                'city' => 'Lisbonne',
            ],
            [
                'name' => [
                    'fr' => 'Workshops Black Milk',
                    'en' => 'Black Milk Workshops',
                    'ro' => 'Workshopuri Black Milk',
                ],
                'type' => 'workshop',
                'description' => [
                    'fr' => 'De nouvelles dates de workshops seront dévoilées prochainement.',
                    'en' => 'New workshop dates will be announced soon.',
                    'ro' => 'Noi date pentru workshopuri vor fi anunțate în curând.',
                ],
                'country' => 'International',
            ],
            [
                'name' => [
                    'fr' => 'Formation Black Milk — Paris',
                    'en' => 'Black Milk Training — Paris',
                    'ro' => 'Curs Black Milk — Paris',
                ],
                'type' => 'training',
                'description' => [
                    'fr' => "Une nouvelle session parisienne dédiée à la précision, à la technique et à l'expérience Black Milk.",
                    'en' => 'A new Paris session dedicated to precision, technique and the Black Milk experience.',
                    'ro' => 'O nouă sesiune pariziană dedicată preciziei, tehnicii și experienței Black Milk.',
                ],
                'starts_at' => '2027-04-10 00:00:00',
                'ends_at' => '2027-04-11 23:59:59',
                'country' => 'France',
                'city' => 'Paris',
            ],
            [
                'name' => [
                    'fr' => 'Formation Black Milk — Brésil',
                    'en' => 'Black Milk Training — Brazil',
                    'ro' => 'Curs Black Milk — Brazilia',
                ],
                'type' => 'international_tour',
                'description' => [
                    'fr' => 'Une nouvelle destination internationale rejoint le calendrier Black Milk.',
                    'en' => 'A new international destination joins the Black Milk calendar.',
                    'ro' => 'O nouă destinație internațională se alătură calendarului Black Milk.',
                ],
                'country' => 'Brésil',
            ],
            [
                'name' => [
                    'fr' => 'Black Milk Forum',
                    'en' => 'Black Milk Forum',
                    'ro' => 'Black Milk Forum',
                ],
                'type' => 'event',
                'description' => [
                    'fr' => 'Le rendez-vous phare de la communauté Black Milk : formation, inspiration, rencontres et expérience de marque.',
                    'en' => 'The flagship gathering of the Black Milk community: training, inspiration, connections and brand experience.',
                    'ro' => 'Întâlnirea principală a comunității Black Milk: formare, inspirație, conexiuni și experiență de brand.',
                ],
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
