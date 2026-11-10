@php
    $switcherUrls = \App\Support\LocaleUrls::all();
@endphp

@if (count($switcherUrls) > 1)
    <nav aria-label="{{ __('Langue') }}" class="flex items-center gap-1 text-xs font-semibold uppercase tracking-widest {{ $class ?? '' }}">
        @foreach ($switcherUrls as $code => $url)
            <a href="{{ $url }}"
               hreflang="{{ $code }}"
               lang="{{ $code }}"
               @if ($code === app()->getLocale()) aria-current="true" @endif
               class="rounded-full px-2 py-1 transition {{ $code === app()->getLocale() ? 'bg-bmlk-brand text-white' : 'text-zinc-700 hover:text-bmlk-brand' }}">
                {{ strtoupper($code) }}
            </a>
        @endforeach
    </nav>
@endif
