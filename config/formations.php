<?php

// Source: design_prototype/formacion/Formations.dc.html (FR catalogue).
// Dates are intentionally excluded: the live schedule is maintained in Events.
return [
    'online' => [
        [
            'slug' => 'black-milk-instructor',
            'name' => 'BLACK MILK INSTRUCTOR',
            'tag' => 'Programme formateur · Certification',
            'description' => 'Un parcours pour apprendre à transmettre la méthode BLACK MILK et structurer ses propres formations.',
            'price' => 1200,
            'image' => 'instructor-book.webp',
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
            'image' => 'wax-tin.webp',
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
            'image' => 'popit-book.webp',
            'points' => [],
        ],
        [
            'slug' => 'wax-manicure-2',
            'name' => 'WAX MANICURE 2.0',
            'tag' => 'Perfectionnement · Niveau avancé',
            'description' => 'Approfondissez la technique Wax Manicure et les protocoles avancés de manucure et de construction F1.',
            'price' => 250,
            'image' => 'wax2-book.webp',
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
            'image' => 'instructor-book.webp',
            'points' => [],
        ],
        [
            'slug' => 'wax-manicure-live',
            'name' => 'WAX MANICURE — LIVE',
            'tag' => 'Une journée intensive · Elena Smirnova',
            'description' => 'Théorie et pratique de la manucure à la cire, avec outils et matériel fournis par l’école.',
            'price' => 600,
            'image' => 'wax-tin.webp',
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
            'image' => 'wax2-book.webp',
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
            'image' => 'popit-book.webp',
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
    // Detailed WAX MANICURE programme (online course). Blank line = new paragraph.
    'wax_facts' => [
        ['value' => '150 €', 'label' => 'Tarif de la formation'],
        ['value' => 'Offerte', 'label' => 'La cire signature'],
        ['value' => '−20 min', 'label' => 'Sur la durée de la prestation'],
        ['value' => 'À vie', 'label' => 'Accès au cours en ligne'],
    ],
    // Gallery order from the prototype (public/img/formations/skin-XX.webp).
    'wax_skins' => ['14', '05', '06', '02', '03', '07', '13', '12', '10', '15', '08', '09', '01'],
    'wax_about' => [
        'La qualité d’une manucure ne dépend pas du nombre de gestes maîtrisés, mais de la compréhension des tissus sur lesquels on travaille. La cuticule, l’éponychium, le ptérygion et le bourrelet proximal sont des structures distinctes, d’épaisseur, de densité et de réaction au travail mécanique différentes. Une peau déshydratée, une hyperkératose ou, à l’inverse, une peau fine et atrophique exigent un choix différent de fraise, de diamètre, de vitesse de rotation et d’angle d’attaque.',
        'La technique signature d’Elena Smirnova repose sur ce principe. La formation s’appuie sur l’anatomie et la physiologie de l’appareil unguéal, à partir desquelles se construisent le protocole de diagnostic et le choix raisonné de l’outil. Vous apprendrez à évaluer l’état des tissus avant de commencer et à adapter les paramètres de travail à chaque cliente, au lieu d’appliquer un protocole unique à toutes.',
        'Cette approche donne un résultat reproductible dans les cas habituellement considérés comme difficiles : cuticule adhérente, peau épaisse des bourrelets, peau fine et facilement traumatisée, peau sèche, ainsi que la peau qui ne se laisse pas traiter correctement par les méthodes standard. Parallèlement, l’élimination des gestes inutiles et répétés réduit la durée totale de la prestation d’au moins 20 minutes.',
        'L’élément clé de la technique est la cire signature d’Elena Smirnova. Contrairement aux huiles et aux crèmes pour cuticules, qui agissent en surface, la cire restaure le film hydrolipidique et agit sur les couches profondes de l’épiderme : la peau retrouve son élasticité, les zones kératinisées s’assouplissent et le travail est plus rapide et plus net. C’est l’association d’une technique fondée sur l’anatomie et de la cire qui assure à la fois le gain de temps et une qualité de résultat qui tient jusqu’au rendez-vous suivant. La cire est offerte avec la formation.',
    ],
    'wax_goals' => [
        'Distinguer les structures de la zone de cuticule — éponychium, vraie cuticule, ptérygion et peau du bourrelet proximal — et savoir lesquelles traiter et lesquelles préserver.',
        'Établir un diagnostic de l’état de la peau en 2 à 3 minutes et en déduire le type et le grain de la fraise, le diamètre, la vitesse de rotation et l’angle d’attaque.',
        'Travailler avec assurance sur les cinq types de peau les plus difficiles : cuticule adhérente, hyperkératose des bourrelets, peau fine facilement traumatisée, peau sèche et déshydratée, peau réfractaire au traitement standard.',
        'Réaliser la prestation au moins 20 minutes plus vite grâce à un enchaînement d’étapes maîtrisé.',
        'Comprendre la composition et l’action physiologique de la cire, l’utiliser pendant la prestation pour accélérer et améliorer le travail, et la recommander à la cliente pour l’entretien à domicile — comme source de revenus complémentaire.',
        'Obtenir un résultat qui tient jusqu’au rendez-vous suivant : sans envies, sans repousse précoce de la cuticule, avec une zone traitée régulière et lisse.',
    ],
    'wax_included' => [
        'Vidéos enregistrées avec gros plans : accès à tout moment, depuis n’importe quel pays, sur n’importe quel appareil.',
        'La cire signature d’Elena Smirnova offerte (envoi par la poste).',
        'Le certificat de fin de formation, envoyé par la poste avec la cire.',
        'Le tableau de sélection fraise / diamètre / vitesse / angle pour chaque type de peau (PDF).',
        'Le protocole de diagnostic de la peau et la check-list de contrôle qualité.',
        'Accès aux supports de formation.',
    ],
    'wax_modules' => [
        [
            'title' => 'Anatomie de l’appareil unguéal',
            'description' => 'Structure de l’appareil unguéal : matrice, tablette unguéale, lit de l’ongle, hyponychium, bourrelets proximal et latéraux, sillons latéraux. Les structures de la zone de cuticule — éponychium, vraie cuticule, ptérygion, peau du bourrelet proximal — et leurs différences du point de vue du traitement. La zone de la matrice comme zone à risque : conséquences d’un traumatisme du bourrelet proximal sur la pousse de la tablette. Particularités du travail dans les sillons latéraux.',
        ],
        [
            'title' => 'Physiologie de la peau périunguéale',
            'description' => 'L’épiderme et la kératinisation : formation de la cuticule, rythme de renouvellement et causes des différences de densité d’une cliente à l’autre. Le film hydrolipidique et le niveau d’hydratation : influence de l’eau, des produits ménagers, des antiseptiques, de la saison et de l’âge sur l’état de la peau et sur la technique. Hyperkératose, déshydratation et atrophie : trois états qui appellent trois approches. Réaction de la peau au frottement, à la pression et à l’échauffement de la fraise ; limites admissibles de l’action mécanique.',
        ],
        [
            'title' => 'Diagnostic : cinq types de peau',
            'description' => 'Le protocole d’examen signature avant de commencer : évaluation visuelle et tactile, questions à la cliente. Les cinq types de peau que rencontre régulièrement la technicienne : cuticule adhérente, hyperkératose des bourrelets, peau fine facilement traumatisée, peau sèche et déshydratée, peau réfractaire au traitement standard. Signes diagnostiques de chaque type, erreurs caractéristiques et raisons pour lesquelles un protocole unique échoue.',
        ],
        [
            'title' => 'L’outil : fraise, diamètre, vitesse, angle',
            'description' => 'Classification des fraises par matériau (diamant, céramique, carbure) et par forme (boule, flamme, cône, lentille, aiguille). Le grain et son marquage ; choix de l’abrasivité selon l’état de la peau. Le diamètre de la fraise et la vitesse de rotation comme paramètres déterminant le résultat sur une même peau. Angle d’attaque, sens de rotation et pression. Le tableau de sélection signature : type de peau → fraise → diamètre → vitesse → angle. Adaptation du tableau à un autre appareil ou à un autre jeu de fraises.',
        ],
        [
            'title' => 'La technique signature',
            'description' => 'Démonstration complète de la manucure à la cire sur une vraie modèle, avec gros plans sur chaque étape. Ordre de traitement des zones : sillons latéraux, bourrelet proximal, éponychium et cuticule, ptérygion, finition. Position de la main de travail, maintien du doigt de la cliente, contrôle de la pression et de l’échauffement. Chronométrage de la prestation : analyse des étapes où le temps se perd et suppression des gestes répétés.',
        ],
        [
            'title' => 'Cas difficiles : les cinq types de peau en pratique',
            'description' => 'Démonstration séparée du traitement pour chacun des cinq types de peau vus au module 3. Modifications du choix de fraise, de vitesse, d’angle et d’enchaînement selon l’état des tissus. Obtention d’un résultat également net, quelle que soit la difficulté initiale.',
        ],
        [
            'title' => 'La cire signature : composition, action, résultats',
            'description' => "Module central de la formation.\n\nCe qui distingue la cire des autres produits. Les huiles et crèmes pour cuticules agissent à la surface de la couche cornée et donnent un effet de courte durée. Grâce à sa structure et à sa composition, la cire reste plus longtemps sur la peau, forme un film protecteur et pénètre dans les couches profondes de l’épiderme. Analyse comparative : huile, crème, dissolvant de cuticules, cire — ce qui se passe dans la peau après chacun d’eux.\n\nRestauration du film hydrolipidique. Comment la cire reconstitue les lipides de la couche cornée, réduit la perte insensible en eau et rend à la peau son élasticité. Pourquoi cela change le comportement de la cuticule : repousse ralentie, disparition de la sécheresse et des gerçures, peau qui ne se déchire plus pendant le travail.\n\nAction sur les couches profondes de la peau. Influence de la cire sur l’état de l’épiderme et de la zone de l’éponychium en usage régulier. Comment la peau de la cliente évolue d’un rendez-vous à l’autre, et pourquoi les techniciennes qui travaillent à la cire ont, avec le temps, de moins en moins de clientes « difficiles ».\n\nEfficacité pendant la prestation. Comment la cire accélère le travail : la couche cornée assouplie s’enlève en un seul passage, sans gestes répétés et sans risque de traumatisme. Incidence sur la durée totale de la prestation et sur la netteté du résultat. Étape, zones et technique d’application dans le protocole signature.\n\nLa cire pour l’entretien à domicile et sa vente. Propriétés de la cire en usage quotidien : maintien du résultat entre deux rendez-vous, protection de la peau contre l’eau et les produits ménagers, prévention des envies. Comment présenter la cire à la cliente, quels mots et quels arguments fonctionnent, comment l’intégrer au prix de la prestation ou la vendre séparément. La cire comme source de revenus complémentaire et comme raison pour laquelle la cliente revient chez vous.",
        ],
        [
            'title' => 'Contrôle qualité',
            'description' => 'Critères d’évaluation d’une manucure terminée. Analyse de ses propres travaux sur photo : repérer les erreurs avant la cliente. Réponses aux questions les plus fréquentes des techniciennes et des formatrices.',
        ],
    ],
];
