<x-layout :title="__('Mon profil | BLACK MILK')" robots="noindex, nofollow">
<main class="mx-auto max-w-6xl px-4 py-10 sm:py-16">
    @include('account.partials.nav')

<section class="max-w-2xl rounded-3xl bg-white p-6 sm:p-9">
    <x-lucide-user-round class="h-6 w-6 text-bmlk-brand" />
    <h1 class="mt-4 font-serif text-3xl text-zinc-900">{{ __('Mon profil') }}</h1>
    <p class="mt-3 text-sm text-zinc-500">{{ $user->name }} · {{ $user->email }}</p>
    <h2 class="mt-9 font-serif text-xl text-zinc-900">{{ $user->password ? __('Changer mon mot de passe') : __('Définir mon mot de passe') }}</h2>
    <div class="mt-4">@include('account.partials.password')</div>
</section>

</main>
</x-layout>
