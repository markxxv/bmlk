<x-layout :title="$metaTitle" robots="noindex, nofollow">
    <main class="mx-auto max-w-2xl px-4 py-16 text-center sm:py-24">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-bmlk-soft text-bmlk-brand">
            <x-lucide-circle-check class="h-7 w-7" />
        </span>
        <h1 class="mt-6 font-serif text-4xl tracking-tight text-zinc-900">{{ __('Merci pour votre achat !') }}</h1>
        <p class="mt-4 text-sm leading-7 text-zinc-600">
            {{ __('Votre paiement pour « :course » est confirmé.', ['course' => $purchase->course->name]) }}
            @if ($purchase->email)
                {{ __('Un e-mail a été envoyé à :email.', ['email' => $purchase->email]) }}
            @endif
        </p>

        @if ($purchase->user && blank($purchase->user->password))
            <p class="mt-6 text-sm leading-7 text-zinc-600">{{ __('Définissez votre mot de passe avec cette adresse e-mail pour accéder à votre formation.') }}</p>
            <a href="{{ route('password.request') }}" class="mt-6 inline-flex h-12 items-center justify-center rounded-full bg-bmlk-brand px-6 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#945763]">
                {{ __('Définir mon mot de passe') }}
            </a>
        @else
            <p class="mt-6 text-sm leading-7 text-zinc-600">{{ __('Connectez-vous avec cette adresse e-mail pour retrouver vos formations.') }}</p>
            <a href="{{ route('account.login') }}" class="mt-6 inline-flex h-12 items-center justify-center rounded-full bg-bmlk-brand px-6 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-[#945763]">
                {{ __('Accéder à ma formation') }}
            </a>
        @endif
    </main>
</x-layout>
