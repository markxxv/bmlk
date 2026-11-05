@props([
    'title' => null,
    'metaDescription' => null,
    'canonical' => null,
    'robots' => 'index, follow',
    'ogType' => 'website',
    'ogImage' => null,
    'adminEditUrl' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? __('BLACK MILK') }}</title>
    <meta name="robots" content="{{ $robots }}">

    @if ($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
    @endif

    @if ($canonical)
        <link rel="canonical" href="{{ $canonical }}">
    @endif

    <meta property="og:site_name" content="{{ __('BLACK MILK') }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $title ?? __('BLACK MILK') }}">

    @if ($metaDescription)
        <meta property="og:description" content="{{ $metaDescription }}">
    @endif

    @if ($canonical)
        <meta property="og:url" content="{{ $canonical }}">
    @endif

    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">


    @stack('head')
    @vite('resources/js/app.js')
</head>

<body class="min-h-screen bg-[#FCF8F4] font-['Hanken_Grotesk'] text-[#3A2E30] antialiased">
    @include('layouts.header')
    <x-cart />

    {{ $slot }}

    @if ($adminEditUrl && auth()->user()?->is_admin)
        <a href="{{ $adminEditUrl }}"
           class="fixed bottom-5 left-5 z-[90] flex size-12 items-center justify-center rounded-full bg-bmlk-brand text-white shadow-lg transition hover:bg-[#572036] hover:shadow-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-bmlk-brand"
           title="Modifier dans l’administration"
           aria-label="Modifier cette page">
            <x-lucide-pencil class="size-5" />
        </a>
    @endif

    @include('layouts.footer')

    @vite('resources/js/mascot.js')
</body>
</html>
