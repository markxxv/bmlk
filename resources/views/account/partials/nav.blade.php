<div class="mb-9 flex flex-wrap items-center justify-between gap-4">
    <a href="{{ route('account.index') }}" class="font-serif text-3xl text-zinc-900">{{ __('Mon espace') }}</a>
    <form method="POST" action="{{ route('account.logout') }}">@csrf
        <button class="inline-flex items-center gap-2 text-sm text-zinc-500 hover:text-zinc-900">
            <x-lucide-log-out class="h-4 w-4" />{{ __('Déconnexion') }}
        </button>
    </form>
</div>
<nav class="mb-8 flex flex-wrap gap-2 text-sm">
    <a class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 transition {{ request()->routeIs('account.index','account.order') ? 'bg-bmlk-brand text-white' : 'bg-white text-zinc-700 hover:bg-bmlk-soft' }}"
        href="{{ route('account.index') }}"><x-lucide-package class="h-4 w-4" aria-hidden="true" />{{ __('Mes commandes') }}</a>
    <a class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 transition {{ request()->routeIs('account.profile') ? 'bg-bmlk-brand text-white' : 'bg-white text-zinc-700 hover:bg-bmlk-soft' }}"
        href="{{ route('account.profile') }}"><x-lucide-user-round class="h-4 w-4" aria-hidden="true" />{{ __('Mon profil') }}</a>
    <a class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 transition {{ request()->routeIs('account.roles') ? 'bg-bmlk-brand text-white' : 'bg-white text-zinc-700 hover:bg-bmlk-soft' }}"
        href="{{ route('account.roles') }}"><x-lucide-handshake class="h-4 w-4" aria-hidden="true" />{{ __('Partenariats') }}</a>
    @if (auth()->user()->is_wholesale && auth()->user()->hasVerifiedEmail())
        <a class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 transition {{ request()->routeIs('account.wholesale') ? 'bg-bmlk-brand text-white' : 'bg-white text-zinc-700 hover:bg-bmlk-soft' }}"
            href="{{ route('account.wholesale') }}"><x-lucide-building-2 class="h-4 w-4" aria-hidden="true" />{{ __('Espace professionnel') }}</a>
    @endif
    @if (auth()->user()->is_influencer && auth()->user()->hasVerifiedEmail())
        <a class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 transition {{ request()->routeIs('account.influencer') ? 'bg-bmlk-brand text-white' : 'bg-white text-zinc-700 hover:bg-bmlk-soft' }}"
            href="{{ route('account.influencer') }}"><x-lucide-megaphone class="h-4 w-4" aria-hidden="true" />{{ __('Influenceur') }}</a>
    @endif
</nav>
@if (session('success'))
    <div class="mb-6 rounded-2xl bg-bmlk-soft px-5 py-4 text-sm text-bmlk-ink">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <p class="mb-6 text-sm text-red-600">{{ $errors->first() }}</p>
@endif
@if (! auth()->user()->hasVerifiedEmail() && ! (request()->routeIs('account.index') && blank(auth()->user()->password)))
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white px-5 py-4 text-sm">
        <p class="text-zinc-600">{{ __('Confirmez votre e-mail pour accéder aux programmes partenaires et à tout votre historique.') }}</p>
        <form method="POST" action="{{ route('verification.send') }}">@csrf
            <button type="submit" class="inline-flex items-center gap-2 font-semibold text-bmlk-brand"><x-lucide-mail-check class="h-4 w-4" aria-hidden="true" />{{ __('Renvoyer le lien') }}</button>
        </form>
    </div>
@endif
