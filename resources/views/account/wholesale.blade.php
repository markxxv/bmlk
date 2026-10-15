<x-layout :title="__('Professionnel | BLACK MILK')" robots="noindex, nofollow">
<main class="mx-auto max-w-6xl px-4 py-10 sm:py-16">
    @include('account.partials.nav')

<section class="max-w-2xl rounded-3xl bg-white p-7 sm:p-10">
    <x-lucide-building-2 class="h-6 w-6 text-bmlk-brand" />
    <h1 class="mt-5 font-serif text-3xl text-zinc-900">{{ __('Mon espace professionnel') }}</h1>
    <p class="mt-4 text-sm leading-7 text-zinc-500">{{ __('Votre statut professionnel est actif. Les tarifs et fonctionnalités dédiés seront disponibles ici prochainement.') }}</p>
</section>

</main>
</x-layout>
