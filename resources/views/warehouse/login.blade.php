<x-warehouse.layout title="Connexion">
    <div class="mx-auto flex min-h-[75dvh] max-w-sm flex-col justify-center">
        <div class="mb-10 text-center">
            <div class="text-2xl font-black tracking-tight">BLACK MILK</div>
            <div class="mt-1 text-[10px] font-bold tracking-[0.4em] text-zinc-500">FRANCE</div>
            <h1 class="mt-8 text-3xl font-bold tracking-tight">{{ __('Espace préparation') }}</h1>
            <p class="mt-2 text-sm text-zinc-500">{{ __('Accès réservé à l’équipe.') }}</p>
        </div>
        <form method="POST" action="{{ route('warehouse.login.submit') }}" class="space-y-4 rounded-3xl bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-semibold" for="email">{{ __('E-mail') }}</label>
                <input class="w-full rounded-xl bg-zinc-100 px-4 py-3.5 text-base outline-none focus:ring-2 focus:ring-[#712B47]" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold" for="password">{{ __('Mot de passe') }}</label>
                <input class="w-full rounded-xl bg-zinc-100 px-4 py-3.5 text-base outline-none focus:ring-2 focus:ring-[#712B47]" id="password" type="password" name="password" autocomplete="current-password" required>
            </div>
            @if ($errors->any())
                <p role="alert" class="text-sm text-rose-700">{{ $errors->first() }}</p>
            @endif
            <button class="flex min-h-13 w-full items-center justify-center rounded-xl bg-[#712B47] px-5 py-3 font-semibold text-white active:opacity-80" type="submit">{{ __('Se connecter') }}</button>
        </form>
        <p class="mt-6 text-center text-xs text-zinc-500">{{ __('Session conservée pendant 14 jours sur cet appareil.') }}</p>
    </div>
</x-warehouse.layout>
