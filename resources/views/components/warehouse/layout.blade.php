@props(['title' => 'À préparer'])

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#712B47">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="BLACK MILK">
    <link rel="manifest" href="{{ asset('pwa-warehouse/manifest.webmanifest') }}">
    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/logo.png') }}">
    <title>{{ $title }} · BLACK MILK</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-dvh bg-[#F8F6F7] font-sans text-zinc-950 antialiased">
    <div class="mx-auto min-h-dvh max-w-xl px-4 pb-28 pt-4 sm:px-6">
        {{ $slot }}
    </div>
    <script>
        if ('serviceWorker' in navigator && location.protocol === 'https:') {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/warehouse/sw.js', {scope: '/warehouse/'}).catch(() => {});
            });
        }
    </script>
</body>
</html>
