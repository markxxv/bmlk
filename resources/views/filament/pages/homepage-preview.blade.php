<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Homepage preview</title>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Hanken+Grotesk:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    <style>
        [data-home-field] { cursor: text; border-radius: 3px; outline-offset: 4px; }
        [data-home-field]:hover, [data-home-field]:focus-visible, [data-home-field][data-editing] { outline: 2px dashed currentColor; }
        body { margin: 0; overflow: hidden; }
    </style>
</head>
<body class="font-['Hanken_Grotesk'] antialiased">
    <x-home-hero :settings="$homepage" :copy="$heroCopy" :preview="true" />
    <div id="hero-inline-editor"></div>
</body>
</html>
