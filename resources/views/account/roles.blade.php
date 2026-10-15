<x-layout :title="__('Partenariats | BLACK MILK')" robots="noindex, nofollow">
<main class="mx-auto max-w-6xl px-4 py-10 sm:py-16">
    @include('account.partials.nav')

<h1 class="mb-6 font-serif text-3xl text-zinc-900">{{ __('Partenariats BLACK MILK') }}</h1>
<p class="mb-8 max-w-2xl text-sm leading-7 text-zinc-500">{{ __('Vous pouvez rejoindre les deux programmes indépendamment. Chaque demande est examinée par notre équipe.') }}</p>
<div class="grid gap-6 md:grid-cols-2">
    @foreach (['wholesale' => ['title' => __('Partenaire professionnel'), 'description' => __('Espace destiné aux distributeurs et achats professionnels.'), 'field' => 'is_wholesale'], 'influencer' => ['title' => __('Influenceur'), 'description' => __('Votre code de réduction personnel pour votre communauté.'), 'field' => 'is_influencer']] as $role => $details)
        <section class="rounded-3xl bg-white p-7">
            <x-lucide-sparkles class="h-6 w-6 text-bmlk-brand" />
            <h2 class="mt-5 font-serif text-2xl text-zinc-900">{{ $details['title'] }}</h2>
            <p class="mt-3 text-sm leading-6 text-zinc-500">{{ $details['description'] }}</p>
            @if ($user->{$details['field']})
                <p class="mt-6 text-sm font-semibold text-emerald-700">{{ __('Statut approuvé') }}</p>
                <a class="mt-4 inline-block text-sm font-medium text-bmlk-brand" href="{{ $role === 'wholesale' ? route('account.wholesale') : route('account.influencer') }}">{{ __('Ouvrir mon espace') }} →</a>
            @elseif (optional($applications->get($role))->status === 'pending')
                <p class="mt-6 text-sm text-bmlk-brand">{{ __('Demande en cours d’examen') }}</p>
            @elseif (! $user->hasVerifiedEmail())
                <p class="mt-6 text-sm text-zinc-500">{{ __('Confirmez votre e-mail pour candidater.') }}</p>
            @else
                <form method="POST" action="{{ route('account.roles.apply') }}" class="mt-6 space-y-3">@csrf
                    <input type="hidden" name="role" value="{{ $role }}">
                    <textarea name="message" rows="3" maxlength="2000" placeholder="{{ __('Parlez-nous de votre activité (facultatif)') }}" class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none focus:border-bmlk-brand"></textarea>
                    <button class="rounded-full bg-bmlk-brand px-6 py-3 text-sm font-medium text-white">{{ __('Envoyer ma demande') }}</button>
                </form>
            @endif
        </section>
    @endforeach
</div>

</main>
</x-layout>
