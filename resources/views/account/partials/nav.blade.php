<div class="mb-9 flex flex-wrap items-center justify-between gap-4">
    <a href="{{ route('account.index') }}" class="font-serif text-3xl text-zinc-900">{{ __('Mon espace') }}</a>
    <form method="POST" action="{{ route('account.logout') }}">@csrf
        <button class="inline-flex items-center gap-2 text-sm text-zinc-500 hover:text-zinc-900">
            <x-lucide-log-out class="h-4 w-4" />{{ __('Déconnexion') }}
        </button>
    </form>
</div>
<nav class="mb-8 flex flex-wrap gap-2 text-sm">
    <a class="rounded-full px-4 py-2.5 {{ request()->routeIs('account.index','account.order') ? 'bg-bmlk-brand text-white' : 'bg-white text-zinc-700 hover:bg-bmlk-soft' }}"
        href="{{ route('account.index') }}">{{ __('Mes commandes') }}</a>
    <a class="rounded-full px-4 py-2.5 {{ request()->routeIs('account.profile') ? 'bg-bmlk-brand text-white' : 'bg-white text-zinc-700 hover:bg-bmlk-soft' }}"
        href="{{ route('account.profile') }}">{{ __('Mon profil') }}</a>
    <a class="rounded-full px-4 py-2.5 {{ request()->routeIs('account.roles') ? 'bg-bmlk-brand text-white' : 'bg-white text-zinc-700 hover:bg-bmlk-soft' }}"
        href="{{ route('account.roles') }}">{{ __('Partenariats') }}</a>
    @if (auth()->user()->is_wholesale && auth()->user()->hasVerifiedEmail())
        <a class="rounded-full px-4 py-2.5 {{ request()->routeIs('account.wholesale') ? 'bg-bmlk-brand text-white' : 'bg-white text-zinc-700 hover:bg-bmlk-soft' }}"
            href="{{ route('account.wholesale') }}">{{ __('Espace professionnel') }}</a>
    @endif
    @if (auth()->user()->is_influencer && auth()->user()->hasVerifiedEmail())
        <a class="rounded-full px-4 py-2.5 {{ request()->routeIs('account.influencer') ? 'bg-bmlk-brand text-white' : 'bg-white text-zinc-700 hover:bg-bmlk-soft' }}"
            href="{{ route('account.influencer') }}">{{ __('Influenceur') }}</a>
    @endif
</nav>
@if (session('success'))
    <div class="mb-6 rounded-2xl bg-bmlk-soft px-5 py-4 text-sm text-bmlk-ink">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <p class="mb-6 text-sm text-red-600">{{ $errors->first() }}</p>
@endif
@if (! auth()->user()->hasVerifiedEmail())
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white px-5 py-4 text-sm">
        <p class="text-zinc-600">{{ __('Confirmez votre e-mail pour accéder aux programmes partenaires et à tout votre historique.') }}</p>
        <form method="POST" action="{{ route('verification.send') }}">@csrf
            <button class="font-semibold text-bmlk-brand">{{ __('Renvoyer le lien') }}</button>
        </form>
    </div>
@endif
