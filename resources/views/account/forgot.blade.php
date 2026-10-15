<x-layout :title="__('Mot de passe | BLACK MILK')" robots="noindex, nofollow">
<main class="mx-auto max-w-6xl px-4 py-10 sm:py-16">


<section class="mx-auto max-w-md rounded-3xl bg-white p-7 sm:p-10">
    <x-lucide-key-round class="h-6 w-6 text-bmlk-brand" />
    <h1 class="mt-5 font-serif text-3xl">{{ __('Créer ou récupérer mon mot de passe') }}</h1>
    <p class="mt-4 text-sm leading-6 text-zinc-500">{{ __('Nous vous enverrons un lien sécurisé. Aucun mot de passe n’est envoyé par e-mail.') }}</p>
    @if (session('success'))<p class="mt-4 text-sm text-emerald-700">{{ session('success') }}</p>@endif
    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">@csrf
        <input type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('E-mail') }}" autocomplete="email" required class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none focus:border-bmlk-brand">
        @if ($errors->any())<p class="text-sm text-red-600">{{ $errors->first() }}</p>@endif
        <button class="w-full rounded-full bg-bmlk-brand px-6 py-3 text-sm font-medium text-white">{{ __('Envoyer le lien') }}</button>
    </form>
</section>

</main>
</x-layout>
