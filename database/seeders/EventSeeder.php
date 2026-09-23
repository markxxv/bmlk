<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'name' => [
                    'fr' => 'Formation BLACK MILK — Paris',
                    'en' => 'BLACK MILK Training — Paris',
                    'ro' => 'Curs BLACK MILK — Paris',
                ],
                'type' => 'training',
                'description' => [
                    'fr' => "Deux jours d'immersion dans l'univers et les techniques BLACK MILK.",
                    'en' => 'Two days of immersion in the BLACK MILK universe and techniques.',
                    'ro' => 'Două zile de imersiune în universul și tehnicile BLACK MILK.',
                ],
                'starts_at' => '2027-01-23 00:00:00',
                'ends_at' => '2027-01-24 23:59:59',
                'country' => 'France',
                'city' => 'Paris',
            ],
            [
                'name' => [
                    'fr' => 'Workshops BLACK MILK',
                    'en' => 'BLACK MILK Workshops',
                    'ro' => 'Workshopuri BLACK MILK',
                ],
                'type' => 'workshop',
                'description' => [
                    'fr' => 'De nouvelles dates de workshops seront dévoilées prochainement.',
                    'en' => 'New workshop dates will be revealed soon.',
                    'ro' => 'Noi date de workshop vor fi dezvăluite curând.',
                ],
                'country' => 'International',
            ],
            [
                'name' => [
                    'fr' => 'Formation BLACK MILK — Amérique',
                    'en' => 'BLACK MILK Training — America',
                    'ro' => 'Curs BLACK MILK — America',
                ],
                'type' => 'international_tour',
                'description' => [
                    'fr' => 'BLACK MILK arrive en Amérique. La ville et les dates seront révélées prochainement.',
                    'en' => 'BLACK MILK is coming to America. City and dates will be revealed soon.',
                    'ro' => 'BLACK MILK ajunge în America. Orașul și datele vor fi dezvăluite curând.',
                ],
                'country' => 'Amérique',
                'city' => 'Ville à venir',
            ],
            [
                'name' => [
                    'fr' => 'Workshops BLACK MILK',
                    'en' => 'BLACK MILK Workshops',
                    'ro' => 'Workshopuri BLACK MILK',
                ],
                'type' => 'workshop',
                'description' => [
                    'fr' => 'De nouvelles dates de workshops seront dévoilées prochainement.',
                    'en' => 'New workshop dates will be revealed soon.',
                    'ro' => 'Noi date de workshop vor fi dezvăluite curând.',
                ],
                'country' => 'International',
            ],
            [
                'name' => [
                    'fr' => 'Instructor Course — Lisbonne',
                    'en' => 'Instructor Course — Lisbon',
                    'ro' => 'Instructor Course — Lisabona',
                ],
                'type' => 'training',
                'description' => [
                    'fr' => 'Quatre jours de formation intensive pour transmettre la méthode BLACK MILK avec exigence, maîtrise et identité.',
                    'en' => 'Four days of intensive training to pass on the BLACK MILK method with rigour, mastery and identity.',
                    'ro' => 'Patru zile de formare intensivă pentru a transmite metoda BLACK MILK cu exigență, măiestrie și identitate.',
                ],
                'starts_at' => '2027-03-11 00:00:00',
                'ends_at' => '2027-03-14 23:59:59',
                'country' => 'Portugal',
                'city' => 'Lisbonne',
            ],
            [
                'name' => [
                    'fr' => 'Workshops BLACK MILK',
                    'en' => 'BLACK MILK Workshops',
                    'ro' => 'Workshopuri BLACK MILK',
                ],
                'type' => 'workshop',
                'description' => [
                    'fr' => 'De nouvelles dates de workshops seront dévoilées prochainement.',
                    'en' => 'New workshop dates will be revealed soon.',
                    'ro' => 'Noi date de workshop vor fi dezvăluite curând.',
                ],
                'country' => 'International',
            ],
            [
                'name' => [
                    'fr' => 'Formation BLACK MILK — Paris',
                    'en' => 'BLACK MILK Training — Paris',
                    'ro' => 'Curs BLACK MILK — Paris',
                ],
                'type' => 'training',
                'description' => [
                    'fr' => "Une nouvelle session parisienne dédiée à la précision, à la technique et à l'expérience BLACK MILK.",
                    'en' => 'A new Paris session dedicated to precision, technique and the BLACK MILK experience.',
                    'ro' => 'O nouă sesiune pariziană dedicată preciziei, tehnicii și experienței BLACK MILK.',
                ],
                'starts_at' => '2027-04-10 00:00:00',
                'ends_at' => '2027-04-11 23:59:59',
                'country' => 'France',
                'city' => 'Paris',
            ],
            [
                'name' => [
                    'fr' => 'Formation BLACK MILK — Brésil',
                    'en' => 'BLACK MILK Training — Brazil',
                    'ro' => 'Curs BLACK MILK — Brazilia',
                ],
                'type' => 'international_tour',
                'description' => [
                    'fr' => 'Une nouvelle destination internationale rejoint le calendrier BLACK MILK.',
                    'en' => 'A new international destination joins the BLACK MILK calendar.',
                    'ro' => 'O nouă destinație internațională se alătură calendarului BLACK MILK.',
                ],
                'country' => 'Brésil',
                'city' => 'Ville à venir',
            ],
            [
                'name' => [
                    'fr' => 'BLACK MILK FORUM',
                    'en' => 'BLACK MILK FORUM',
                    'ro' => 'BLACK MILK FORUM',
                ],
                'type' => 'event',
                'description' => [
                    'fr' => 'Le rendez-vous phare de la communauté BLACK MILK : formation, inspiration, rencontres et expérience de marque.',
                    'en' => 'The flagship gathering of the BLACK MILK community: training, inspiration, encounters and brand experience.',
                    'ro' => 'Întâlnirea-vedetă a comunității BLACK MILK: formare, inspirație, întâlniri și experiență de brand.',
                ],
            ],
        ];

        foreach ($events as $index => $data) {
            Event::query()->updateOrCreate(
                [
                    'sort' => $index + 1,
                ],
                [
                    ...$data,
                    'url' => null,
                    'address' => null,
                    'price' => null,
                    'ticket_url' => null,
                    'active' => true,
                    'sort' => $index + 1,
                ],
            );
        }
    }
}
