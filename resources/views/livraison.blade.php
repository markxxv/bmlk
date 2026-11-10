<x-layout
    :title="__('Livraison et délais | BLACK MILK France')"
    :meta-description="__('Découvrez comment sont calculés les délais de livraison BLACK MILK : 3 à 5 jours ouvrés, horaires de traitement, week-ends et jours fériés.')"
    :canonical="route('delivery.terms')"
>
    @php
        // Translations may contain <b>…</b>: they are static lang strings, never user input.
        $rich = fn (string $key, array $replace = []) => str_replace(
            ['<b>', '</b>'],
            ['<strong class="font-semibold text-zinc-900">', '</strong>'],
            __($key, $replace),
        );
    @endphp
    <main class="px-3 pb-16 pt-5 sm:px-5 sm:pb-20 lg:px-8">
        <div class="mx-auto max-w-[1560px]">
            <section class="relative overflow-hidden rounded-3xl bg-[#EFDDE0] px-6 py-12 sm:px-10 sm:py-16 lg:px-16 lg:py-20">
                <div class="grid items-center gap-10 lg:grid-cols-12">
                    <div class="lg:col-span-8">
                        <p class="text-sm font-medium text-bmlk-brand">{{ __('Informations de livraison') }}</p>
                        <h1 class="mt-5 max-w-3xl font-serif text-4xl font-medium tracking-tight text-[#3A2E30] sm:text-5xl lg:text-6xl">
                            {{ __('La livraison, en toute clarté') }}
                        </h1>
                        <p class="mt-6 max-w-2xl text-base leading-8 text-[#63545A]">
                            {{ __('Pour savoir précisément à partir de quand votre délai est calculé, quels jours comptent et ce qui se passe si vous commandez le soir ou le week-end.') }}
                        </p>
                    </div>
                    <div class="rounded-3xl bg-white/80 px-8 py-8 lg:col-span-4 lg:px-10 lg:py-10">
                        <p class="text-sm font-medium text-bmlk-brand">{{ __('Délai standard annoncé') }}</p>
                        <p class="mt-3 font-serif text-5xl font-medium tracking-tight text-[#3A2E30] sm:text-6xl">3–5</p>
                        <p class="mt-2 text-lg font-medium text-[#3A2E30]">{{ __('jours ouvrés') }}</p>
                        <p class="mt-4 text-sm leading-6 text-[#786970]">{{ __('Sauf délai différent indiqué avant le paiement pour votre destination ou votre mode de livraison.') }}</p>
                    </div>
                </div>
            </section>

            <div class="mt-5 grid gap-5 lg:grid-cols-12">
                <section class="rounded-3xl bg-white p-7 sm:p-10 lg:col-span-7 lg:p-12">
                    <div class="flex items-start gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#F6ECEE] text-bmlk-brand"><x-lucide-clock-3 class="size-5" /></span>
                        <div>
                            <h2 class="font-serif text-3xl font-medium tracking-tight text-zinc-900">{{ __('Quels jours sont comptés ?') }}</h2>
                            <p class="mt-4 max-w-2xl text-sm leading-7 text-zinc-600 sm:text-base">
                                {!! $rich('Les délais sont calculés en <b>jours ouvrés, du lundi au vendredi, de 10 h à 19 h (heure de Paris)</b>. Les heures en dehors de cette plage, les samedis, les dimanches et les jours fériés légaux en France ne sont pas comptabilisés.') !!}
                            </p>
                            <p class="mt-4 max-w-2xl text-sm leading-7 text-zinc-600 sm:text-base">
                                {!! $rich('Ces horaires correspondent au <b>traitement des commandes</b> : ils ne constituent pas un rendez-vous ni une plage de passage garantie du transporteur.') !!}
                            </p>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl bg-white p-7 sm:p-10 lg:col-span-5 lg:p-12">
                    <div class="flex items-start gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#F6ECEE] text-bmlk-brand"><x-lucide-calendar-clock class="size-5" /></span>
                        <div>
                            <h2 class="font-serif text-3xl font-medium tracking-tight text-zinc-900">{{ __('Quand commence le délai ?') }}</h2>
                            <p class="mt-4 text-sm leading-7 text-zinc-600 sm:text-base">
                                {!! $rich('Le point de départ est la confirmation d’une commande <b>validée et payée</b>, pendant les horaires de traitement. Une commande confirmée avant 10 h, après 19 h, le week-end ou un jour férié est prise en compte <b>à 10 h le prochain jour ouvré</b>.') !!}
                            </p>
                            <p class="mt-4 text-sm leading-7 text-zinc-600 sm:text-base">
                                {!! $rich('Un panier enregistré ou un paiement en attente ne déclenche pas le délai.') !!}
                            </p>
                        </div>
                    </div>
                </section>
            </div>

            <section class="mt-5 rounded-3xl bg-[#F6ECEE] p-7 sm:p-10 lg:p-12">
                <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
                    <div class="lg:col-span-5">
                        <p class="text-sm font-medium text-bmlk-brand">{{ __('Exemple concret') }}</p>
                        <h2 class="mt-3 font-serif text-3xl font-medium tracking-tight text-zinc-900 sm:text-4xl">
                            {{ __('Commande le vendredi à 19 h 30') }}
                        </h2>
                        <p class="mt-4 max-w-lg text-sm leading-7 text-zinc-600 sm:text-base">
                            {!! $rich('Les bureaux sont fermés. <b>Le samedi et le dimanche ne comptent pas.</b> En l’absence de jour férié, le décompte commence le <b>lundi à 10 h</b>.') !!}
                        </p>
                    </div>
                    <div class="lg:col-span-7">
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-5 sm:gap-3">
                            @foreach ([
                                ['day' => __('Lundi'), 'number' => '1', 'delivery' => false],
                                ['day' => __('Mardi'), 'number' => '2', 'delivery' => false],
                                ['day' => __('Mercredi'), 'number' => '3', 'delivery' => true],
                                ['day' => __('Jeudi'), 'number' => '4', 'delivery' => true],
                                ['day' => __('Vendredi'), 'number' => '5', 'delivery' => true],
                            ] as $day)
                                <div class="rounded-2xl p-4 {{ $day['delivery'] ? 'bg-white' : 'bg-white/50' }}">
                                    <p class="text-sm font-medium text-zinc-900">{{ $day['day'] }}</p>
                                    <p class="mt-5 font-serif text-3xl text-bmlk-brand">{{ $day['number'] }}</p>
                                    <p class="mt-1 text-xs leading-5 text-zinc-500">
                                        {{ $day['delivery'] ? __('Jour de livraison possible') : __('Jour ouvré') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-4 text-xs leading-6 text-[#786970]">
                            {{ __('Dans cet exemple, la livraison standard est prévue entre le mercredi et le vendredi, si aucun jour férié ou délai particulier ne s’applique.') }}
                        </p>
                    </div>
                </div>
            </section>

            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <section class="rounded-3xl bg-white p-7 sm:p-10 lg:p-12">
                    <h2 class="font-serif text-3xl font-medium tracking-tight text-zinc-900">{{ __('Et s’il y a un jour férié ?') }}</h2>
                    <p class="mt-4 text-sm leading-7 text-zinc-600 sm:text-base">
                        {!! $rich('Chaque jour férié non ouvré est exclu du décompte. Si le lundi de notre exemple est férié, le premier jour compté devient <b>le mardi à 10 h</b> ; la plage de livraison se décale d’autant. La même règle s’applique à tout jour férié intervenant pendant le délai.') !!}
                    </p>
                    <p class="mt-4 text-sm leading-7 text-zinc-600 sm:text-base">
                        {!! $rich('Selon le pays de destination, d’autres jours non ouvrés peuvent s’ajouter ; un délai spécifique vous est alors communiqué avant la validation de la commande.') !!}
                    </p>
                </section>

                <section class="rounded-3xl bg-white p-7 sm:p-10 lg:p-12">
                    <h2 class="font-serif text-3xl font-medium tracking-tight text-zinc-900">{{ __('Livraison et suivi de votre colis') }}</h2>
                    <p class="mt-4 text-sm leading-7 text-zinc-600 sm:text-base">
                        {!! $rich('Le mode de livraison, les frais correspondants et le délai applicable à votre destination sont présentés avant le paiement. En cas de délai différent expressément annoncé pour le mode de transport choisi, <b>ce délai est celui de votre commande</b>.') !!}
                    </p>
                    <p class="mt-4 text-sm leading-7 text-zinc-600 sm:text-base">
                        {!! $rich('Dès l’expédition, les informations de suivi disponibles vous permettent de connaître l’acheminement de votre colis. Les jours ouvrés décrivent notre méthode de calcul du délai ; ils ne signifient pas que le transporteur livre nécessairement entre 10 h et 19 h.') !!}
                    </p>
                </section>
            </div>

            <section class="mt-5 rounded-3xl bg-white p-7 sm:p-10 lg:p-12">
                <div class="grid gap-6 lg:grid-cols-12 lg:gap-12">
                    <div class="lg:col-span-4">
                        <h2 class="font-serif text-3xl font-medium tracking-tight text-zinc-900">{{ __('En cas de retard') }}</h2>
                    </div>
                    <div class="lg:col-span-8">
                        <p class="text-sm leading-7 text-zinc-600 sm:text-base">
                            {!! $rich('Si nous prévoyons un retard par rapport au délai annoncé pour votre commande, contactez-nous à :email. Nous vous communiquerons les informations dont nous disposons et chercherons une solution.', ['email' => '<a class="font-medium text-bmlk-brand underline underline-offset-4" href="mailto:office.blackmilk@gmail.com">office.blackmilk@gmail.com</a>']) !!}
                        </p>
                        <p class="mt-4 text-sm leading-7 text-zinc-600 sm:text-base">
                            {!! $rich('<b>Vos droits légaux restent pleinement applicables.</b> En cas de livraison hors du délai convenu, le Code de la consommation prévoit notamment la possibilité de demander une livraison dans un délai supplémentaire raisonnable, puis, si nécessaire, de résoudre le contrat et d’obtenir le remboursement dans les conditions prévues par la loi. Dans certains cas, la résolution peut être immédiate. Les présentes informations ne limitent aucun de ces droits.') !!}
                        </p>
                        <div class="mt-7 flex flex-wrap items-center gap-x-7 gap-y-4 text-sm">
                            <a href="{{ route('page', ['slug' => 'conditions-generales-de-vente']) }}" class="inline-flex items-center gap-2 font-medium text-bmlk-brand transition hover:text-[#945763]">
                                {{ __('Consulter les conditions générales de vente') }}
                                <x-lucide-arrow-up-right class="size-4" />
                            </a>
                            <a href="{{ route('shop.index') }}" class="inline-flex items-center gap-2 text-zinc-600 transition hover:text-bmlk-brand">
                                <x-lucide-arrow-left class="size-4" />
                                {{ __('Retour à la boutique') }}
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</x-layout>
