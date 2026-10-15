<x-layout :title="__('Influenceur | BLACK MILK')" robots="noindex, nofollow">
<main class="mx-auto max-w-6xl px-4 py-10 sm:py-16">
    @include('account.partials.nav')

<section class="max-w-2xl rounded-3xl bg-white p-7 sm:p-10">
    <x-lucide-megaphone class="h-6 w-6 text-bmlk-brand" />
    <h1 class="mt-5 font-serif text-3xl text-zinc-900">{{ __('Mon espace influenceur') }}</h1>
    <p class="mt-3 text-sm text-zinc-500">{{ __('Réduction offerte avec votre code : :percent %', ['percent' => number_format($user->influencer_discount, 0)]) }}</p>
    @if ($user->influencer_code)
        <div class="mt-8 rounded-2xl bg-bmlk-soft px-6 py-7">
            <p class="text-xs text-zinc-500">{{ __('Votre code permanent') }}</p>
            <p class="mt-2 font-serif text-3xl tracking-wider text-bmlk-ink">{{ $user->influencer_code }}</p>
        </div>
    @else
        <form method="POST" action="{{ route('account.influencer.code') }}" class="mt-8 space-y-4">@csrf
            <label for="promo-code" class="block text-sm font-medium text-zinc-900">{{ __('Choisissez votre code unique') }}</label>
            <input id="promo-code" name="code" value="{{ old('code') }}" maxlength="24" minlength="4" required
                pattern="[A-Za-z0-9_-]{4,24}" autocomplete="off" placeholder="MONCODE"
                class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm uppercase outline-none focus:border-bmlk-brand">
            <p class="text-xs leading-5 text-zinc-500">{{ __('De 4 à 24 caractères. Lettres, chiffres, tirets et underscores. Ce code ne peut plus être modifié.') }}</p>
            <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-bmlk-brand px-6 py-3 text-sm font-medium text-white"><x-lucide-badge-check class="h-4 w-4" aria-hidden="true" />{{ __('Enregistrer mon code') }}</button>
        </form>
    @endif
</section>

</main>
</x-layout>
