<x-layout :title="__('Nouveau mot de passe | BLACK MILK')" robots="noindex, nofollow">
<main class="mx-auto max-w-6xl px-4 py-10 sm:py-16">


<section class="mx-auto max-w-md rounded-3xl bg-white p-7 sm:p-10">
    <h1 class="font-serif text-3xl">{{ __('Nouveau mot de passe') }}</h1>
    <form method="POST" action="{{ route('password.update') }}" class="mt-7 space-y-4">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required placeholder="{{ __('E-mail') }}" class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm">
        <input type="password" name="password" autocomplete="new-password" required minlength="12" placeholder="{{ __('Mot de passe (12 caractères minimum)') }}" class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm">
        <input type="password" name="password_confirmation" autocomplete="new-password" required minlength="12" placeholder="{{ __('Confirmer le mot de passe') }}" class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm">
        @if ($errors->any())<p class="text-sm text-red-600">{{ $errors->first() }}</p>@endif
        <button class="w-full rounded-full bg-bmlk-brand px-6 py-3 text-sm font-medium text-white">{{ __('Enregistrer') }}</button>
    </form>
</section>

</main>
</x-layout>
