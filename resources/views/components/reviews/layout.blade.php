@props(['title' => 'Votre avis | BLACK MILK'])

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    @vite('resources/js/reviews.js')
</head>
<body class="min-h-screen bg-[#FCF8F4] font-['Hanken_Grotesk'] text-[#3A2E30] antialiased">
    <header class="px-4 py-5 sm:py-7">
        <a href="{{ route('home') }}" class="mx-auto block w-fit text-center font-serif text-2xl tracking-wide text-bmlk-brand" aria-label="BLACK MILK — Accueil">
            Black Milk
        </a>
    </header>
    {{ $slot }}
</body>
</html>
