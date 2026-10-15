<x-layout :title="__('Connexion | BLACK MILK')" robots="noindex, nofollow">
<main class="mx-auto max-w-6xl px-4 py-10 sm:py-16">


<section class="mx-auto max-w-md rounded-3xl bg-white p-7 sm:p-10">
    <x-lucide-user-round class="h-6 w-6 text-bmlk-brand" />
    <h1 class="mt-5 font-serif text-3xl">{{ __('Se connecter') }}</h1>
    @if (session('success'))<p class="mt-4 text-sm text-emerald-700">{{ session('success') }}</p>@endif
    <form method="POST" action="{{ route('account.login.submit') }}" class="mt-7 space-y-4">@csrf
        <div><label class="mb-2 block text-sm">{{ __('E-mail') }}</label><input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none focus:border-bmlk-brand"></div>
        <div><label class="mb-2 block text-sm">{{ __('Mot de passe') }}</label><input type="password" name="password" autocomplete="current-password" required class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none focus:border-bmlk-brand"></div>
        @if ($errors->any())<p class="text-sm text-red-600">{{ $errors->first() }}</p>@endif
        <button class="w-full rounded-full bg-bmlk-brand px-6 py-3 text-sm font-medium text-white">{{ __('Connexion') }}</button>
    </form>
    <a href="{{ route('password.request') }}" class="mt-5 block text-center text-sm text-bmlk-brand">{{ __('Créer ou récupérer mon mot de passe') }}</a>
</section>

</main>
</x-layout>
