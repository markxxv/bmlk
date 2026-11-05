<?php

// Curated source: design_prototype/formacion/Formations.dc.html (FR catalogue).
// Dates are intentionally excluded: the live schedule is maintained in Events.
return [
    'online' => [
        [
            'slug' => 'black-milk-instructor',
            'name' => 'BLACK MILK INSTRUCTOR',
            'tag' => 'Programme formateur · Certification',
            'description' => 'Un parcours pour apprendre à transmettre la méthode BLACK MILK et structurer ses propres formations.',
            'price' => 1200,
            'image' => 'instructor-book.jpg',
            'points' => [
                'Trois programmes pédagogiques prêts à l’emploi',
                'Cinq manuels théoriques illustrés',
                'Programmes de formation sur quatre jours et deux niveaux',
                'Accompagnement personnalisé avec Elena Smirnova',
            ],
        ],
        [
            'slug' => 'wax-manicure',
            'name' => 'WAX MANICURE',
            'tag' => 'Méthode signature · Accès à vie',
            'description' => 'Maîtrisez la méthode de manucure à la cire d’Elena Smirnova, de l’analyse de la peau au geste de finition.',
            'price' => 150,
            'image' => 'wax-tin.jpg',
            'points' => [
                'Accès à vie au cours en ligne',
                'Technique de manucure à la cire',
                'Introduction à la technique Pop It',
                'Cire BLACK MILK et cutter offerts',
            ],
        ],
        [
            'slug' => 'pop-it-architect',
            'name' => 'POP-IT ARCHITECT',
            'tag' => 'Construction · Upper Forms',
            'description' => 'Une formation spécialisée dans la construction et l’architecture de l’ongle avec les formes supérieures.',
            'price' => 150,
            'image' => 'popit-book.jpg',
            'points' => [],
        ],
        [
            'slug' => 'wax-manicure-2',
            'name' => 'WAX MANICURE 2.0',
            'tag' => 'Perfectionnement · Niveau avancé',
            'description' => 'Approfondissez la technique Wax Manicure et les protocoles avancés de manucure et de construction F1.',
            'price' => 250,
            'image' => 'wax2-book.jpg',
            'points' => [
                'Accès à vie au cours en ligne',
                'Perfectionnement de la manucure à la cire',
                'Technique F1',
                'Cire BLACK MILK et cutter offerts',
            ],
        ],
        [
            'slug' => 'instagram-creator',
            'name' => 'INSTAGRAM CREATOR',
            'tag' => 'Création de contenu · Nail & beauty',
            'description' => 'Apprenez à créer des contenus professionnels pour Instagram et les réseaux sociaux dans l’univers nail & beauty.',
            'price' => null,
            'image' => null,
            'points' => [],
        ],
    ],
    'live' => [
        [
            'slug' => 'black-milk-instructor-live',
            'name' => 'BLACK MILK INSTRUCTOR — LIVE',
            'tag' => 'Certification formateur · Présentiel',
            'description' => 'Un programme de formation en présentiel pour devenir instructeur certifié BLACK MILK.',
            'price' => 3000,
            'image' => 'instructor-book.jpg',
            'points' => [],
        ],
        [
            'slug' => 'wax-manicure-live',
            'name' => 'WAX MANICURE — LIVE',
            'tag' => 'Une journée intensive · Elena Smirnova',
            'description' => 'Théorie et pratique de la manucure à la cire, avec outils et matériel fournis par l’école.',
            'price' => 600,
            'image' => 'wax-tin.jpg',
            'points' => [
                'Anatomie de l’appareil unguéal et typologie de la peau',
                'Choix du protocole selon la peau',
                'Manuel pédagogique et certificat de participation',
                'Coffret cadeau BLACK MILK',
            ],
        ],
        [
            'slug' => 'wax-manicure-2-live',
            'name' => 'WAX MANICURE 2.0 — LIVE',
            'tag' => 'Deux jours · Elena Smirnova',
            'description' => 'Perfectionnement de la manucure à la cire, de l’analyse des tissus à la technique Pop It Mold F1.',
            'price' => 700,
            'image' => 'wax2-book.jpg',
            'points' => [
                'Technique Pop It Mold F1',
                'Exercices pratiques avec Elena Smirnova',
                'Manuel méthodologique',
                'Coffret cadeau BLACK MILK',
            ],
        ],
        [
            'slug' => 'pop-it-architect-live',
            'name' => 'POP-IT ARCHITECT — LIVE',
            'tag' => 'Construction · Upper Forms',
            'description' => 'Formation en présentiel dédiée à la construction et à l’architecture de l’ongle avec les formes supérieures.',
            'price' => 600,
            'image' => 'popit-book.jpg',
            'points' => [],
        ],
        [
            'slug' => 'instagram-creator-private',
            'name' => 'INSTAGRAM CREATOR — PRIVATE',
            'tag' => 'Expérience individuelle · 1:1',
            'description' => 'Une formation en présentiel, au format individuel, dédiée à la création de contenu nail & beauty.',
            'price' => null,
            'image' => null,
            'points' => [],
        ],
    ],
    'wax_modules' => [
        [
            'title' => 'Anatomie de l’appareil unguéal',
            'description' => 'Matrice, plaque, lit de l’ongle et structures périunguéales. Différencier la cuticule, l’éponychium et le ptérygion avant toute intervention.',
        ],
        [
            'title' => 'Physiologie de la peau périunguéale',
            'description' => 'Kératinisation, hydratation, film hydrolipidique et réactions des tissus à la pression, au frottement et à l’échauffement.',
        ],
        [
            'title' => 'Diagnostic des cinq types de peau',
            'description' => 'Identifier les particularités de la peau et adapter le protocole aux cuticules adhérentes, peaux épaisses, fines, sèches ou difficiles à traiter.',
        ],
        [
            'title' => 'Choix de l’outil et des paramètres',
            'description' => 'Sélectionner la forme, le grain et le diamètre de la fraise, puis ajuster vitesse, pression et angle de travail.',
        ],
        [
            'title' => 'La technique signature',
            'description' => 'Démonstration complète, enchaînement des gestes, contrôle de la pression et optimisation de la durée de prestation.',
        ],
        [
            'title' => 'Cas pratiques et peaux difficiles',
            'description' => 'Études de cas comparées et adaptation des gestes aux différents types de peau.',
        ],
        [
            'title' => 'La cire signature BLACK MILK',
            'description' => 'Propriétés de la cire, place dans le protocole de finition et conseils d’entretien à transmettre à la cliente.',
        ],
        [
            'title' => 'Contrôle qualité et analyse des résultats',
            'description' => 'Vérifier la qualité du travail, repérer les erreurs sur photo et consolider les acquis.',
        ],
    ],
];
