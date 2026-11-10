<x-reviews.layout :title="__('Notifications d’avis | BLACK MILK')" robots="noindex, nofollow">
    <main class="mx-auto max-w-xl px-4 py-12 sm:py-20">
        <section class="rounded-3xl bg-white p-6 text-center sm:p-9">
            <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-bmlk-soft text-bmlk-brand">
                <x-lucide-mail-x class="size-6" aria-hidden="true" />
            </span>
            <h1 class="mt-5 font-serif text-3xl text-zinc-900">{{ __('Demandes d’avis') }}</h1>

            @if ($unsubscribed)
                <p class="mt-4 text-sm leading-7 text-zinc-600">
                    {{ __('Votre adresse a été retirée des futurs e-mails demandant une évaluation. Les informations essentielles concernant vos commandes restent envoyées.') }}
                </p>
            @else
                <p class="mt-4 text-sm leading-7 text-zinc-600">
                    {{ __('Vous pouvez choisir de ne plus recevoir d’e-mails vous invitant à évaluer vos commandes BLACK MILK.') }}
                </p>
                <form method="POST" action="{{ request()->fullUrl() }}" class="mt-7">
                    @csrf
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-full bg-bmlk-brand px-6 py-3.5 text-sm font-medium text-white sm:w-auto">
                        {{ __('Ne plus recevoir ces e-mails') }}
                    </button>
                </form>
            @endif
        </section>
    </main>
</x-reviews.layout>
