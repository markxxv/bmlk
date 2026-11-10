<x-layout :title="$metaTitle" :meta-description="$metaDescription" :canonical="$canonical">
    <main
        x-data="{
            mode: @js(old('mode', 'sharpening') === 'diagnostic' ? 'diagnostic' : 'sharpening'),
            scissors: Number(@js(old('scissors', 0))),
            pliers: Number(@js(old('pliers', 0))),
            pushers: Number(@js(old('pushers', 0))),
            plan: @js(old('plan', 'master')),
            subscriptionOpen: @js(old('mode') === 'subscription'),
            photos: [],
            get count() { return this.scissors + this.pliers + this.pushers },
            get amount() { return this.scissors * 10 + this.pliers * 10 + this.pushers * 9 },
            change(kind, delta) { this[kind] = Math.max(0, Math.min(50, this[kind] + delta)) }
        }"
    >
        {{-- Hero aligned with Formations and Créatrices --}}
        <section class="px-3 pb-4 pt-5 sm:px-5 sm:pb-6 lg:px-8" aria-labelledby="repair-title">
            <div class="relative mx-auto max-w-[1560px] overflow-hidden rounded-3xl bg-[#DDA1AA]">
                <div class="px-6 pb-11 pt-10 sm:px-10 sm:pb-14 sm:pt-14 lg:px-14 lg:pt-16 xl:px-16">
                    <div class="max-w-4xl">
                        <div class="flex items-center gap-3" data-reveal data-reveal-y="10">
                            <span class="h-px w-8 shrink-0 bg-accent"></span>
                            <p class="text-sm font-semibold text-accent">{{ __('L’atelier Black Milk') }}</p>
                        </div>
                        <h1 id="repair-title" class="mt-5 font-serif text-4xl font-medium leading-tight tracking-tight text-bmlk-ink sm:text-6xl lg:text-7xl" data-reveal-title data-reveal-title-duration="1">
                            {{ __('Réparation') }} <span class="italic text-white">{{ __('d’outils') }}</span>
                        </h1>
                        <p class="mt-5 max-w-2xl text-base leading-7 text-[#46343A] sm:text-lg sm:leading-8" data-reveal data-reveal-y="12" data-reveal-delay="0.1">
                            {{ __('Affûtage et remise en état de vos ciseaux, pinces et repousse-peaux. Vous envoyez, notre atelier répare et vous renvoie vos outils.') }}
                        </p>
                    </div>
                    <div class="mt-8 flex flex-wrap gap-2 sm:mt-9" role="group" aria-label="{{ __('Type de prestation') }}" data-reveal data-reveal-y="12" data-reveal-delay="0.15">
                        <button type="button" @click="mode = 'sharpening'" :aria-pressed="mode === 'sharpening'" :class="mode === 'sharpening' ? 'bg-accent text-white' : 'bg-bmlk-soft text-accent hover:bg-white'" class="min-h-12 rounded-full px-6 py-3 text-xs font-semibold transition-colors sm:px-7">{{ __('Affûtage') }}</button>
                        <button type="button" @click="mode = 'diagnostic'" :aria-pressed="mode === 'diagnostic'" :class="mode === 'diagnostic' ? 'bg-accent text-white' : 'bg-bmlk-soft text-accent hover:bg-white'" class="min-h-12 rounded-full px-6 py-3 text-xs font-semibold transition-colors sm:px-7">{{ __('Réparation · diagnostic gratuit') }}</button>
                    </div>
                </div>
                <div class="bmlk-packaging-stripe pointer-events-none absolute inset-x-0 bottom-0" aria-hidden="true"></div>
            </div>
        </section>

        <section class="px-3 pb-6 sm:px-5 sm:pb-8 lg:px-8" aria-label="{{ __('Choisir une prestation') }}">
            <div class="mx-auto max-w-[1560px]">
                    @if (session('success'))
                        <div role="status" class="mt-7 max-w-3xl rounded-2xl bg-white px-5 py-4 text-sm font-medium text-accent">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div role="alert" class="mt-7 max-w-3xl rounded-2xl bg-white px-5 py-4 text-sm text-red-700">{{ $errors->first() }}</div>
                    @endif
                    <div class="grid items-start gap-5 lg:grid-cols-2 lg:gap-6">
                        <div class="min-w-0 rounded-3xl bg-white p-5 sm:p-8 lg:p-9">
                            <form x-show="mode === 'sharpening'" method="POST" action="{{ route('reparation.submit') }}" x-cloak>
                                @csrf
                                <input type="hidden" name="mode" value="sharpening">
                                <div class="absolute -left-[10000px]" aria-hidden="true"><label>{{ __('Site web') }} <input name="website" tabindex="-1" autocomplete="off"></label></div>
                                <p class="text-xs font-semibold text-accent">{{ __('Étape 1 · Vos outils') }}</p>
                                <h2 class="mt-3 font-serif text-3xl text-bmlk-ink">{{ __('Préparez votre affûtage') }}</h2>
                                <p class="mt-2 text-sm leading-6 text-[#776A70]">{{ __('Sélectionnez les quantités. Notre atelier vous confirmera l’envoi et le règlement.') }}</p>

                                <div class="mt-7 divide-y divide-[#E7CCD5]">
                                    @foreach ([[__('Ciseaux'), 'scissors', 10], [__('Pince'), 'pliers', 10], [__('Repousse-peaux'), 'pushers', 9]] as [$label, $key, $price])
                                        <div class="flex items-center gap-3 py-4 first:pt-0 sm:gap-5">
                                            <div class="min-w-0 flex-1">
                                                <p class="font-serif text-xl text-bmlk-ink sm:text-2xl">{{ $label }}</p>
                                                <p class="text-xs text-[#776A70]">{{ __('Affûtage · :price € / outil', ['price' => $price]) }}</p>
                                            </div>
                                            <div class="flex shrink-0 items-center rounded-full bg-bmlk-soft">
                                                <button type="button" @click="change('{{ $key }}', -1)" class="flex size-10 items-center justify-center rounded-full text-accent transition hover:bg-[#E9D6DE]" aria-label="{{ __('Retirer :tool', ['tool' => mb_strtolower($label)]) }}"><x-lucide-minus class="size-4" /></button>
                                                <output class="w-7 text-center text-sm font-semibold tabular-nums text-bmlk-ink" x-text="{{ $key }}">0</output>
                                                <button type="button" @click="change('{{ $key }}', 1)" class="flex size-10 items-center justify-center rounded-full text-accent transition hover:bg-[#E9D6DE]" aria-label="{{ __('Ajouter :tool', ['tool' => mb_strtolower($label)]) }}"><x-lucide-plus class="size-4" /></button>
                                            </div>
                                            <p class="w-14 shrink-0 text-right font-serif text-xl tabular-nums text-accent sm:w-16" x-text="({{ $key }} * {{ $price }}) + ' €'">0 €</p>
                                            <input type="hidden" name="{{ $key }}" :value="{{ $key }}">
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-2 rounded-2xl bg-[#E7EFF4] px-4 py-4" aria-live="polite">
                                    <p class="text-xs font-semibold leading-5 text-[#34414B]" x-text="count >= 10 ? @js(__('Livraison aller-retour offerte pour votre sélection')) : (10 - count > 1 ? @js(__('Ajoutez encore :count outils : la livraison aller-retour sera offerte')) : @js(__('Ajoutez encore :count outil : la livraison aller-retour sera offerte'))).replace(':count', 10 - count)"></p>
                                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/80">
                                        <div class="h-full rounded-full bg-accent transition-all" :style="'width: ' + Math.min(100, count * 10) + '%'"></div>
                                    </div>
                                </div>
                                <div class="mt-5 flex items-center justify-between gap-4 border-b border-[#DCC2CA] pb-5 text-xs text-[#776A70]">
                                    <span>{{ __('Livraison aller-retour') }}</span>
                                    <span class="text-right font-medium text-bmlk-ink" x-text="count >= 10 ? @js(__('Offerte')) : @js(__('Calculée par le transporteur'))"></span>
                                </div>
                                <div class="mt-5 flex items-end justify-between gap-3">
                                    <span class="text-xs font-semibold text-bmlk-ink">{{ __('Total indicatif') }}</span>
                                    <strong class="font-serif text-3xl font-normal text-accent sm:text-4xl" x-text="amount + ' €'">0 €</strong>
                                </div>
                                <p class="mt-2 text-right text-xs text-[#776A70]">{{ __('Hors frais d’expédition éventuels') }}</p>

                                <div class="mt-7 grid gap-4 sm:grid-cols-2">
                                    <label class="block text-sm font-semibold text-bmlk-ink">{{ __('Nom complet') }} <span class="font-normal normal-case text-[#776A70]">{{ __('(facultatif)') }}</span>
                                        <input name="name" type="text" maxlength="100" autocomplete="name" value="{{ old('mode') === 'sharpening' ? old('name') : auth()->user()?->name }}" placeholder="{{ __('Votre nom') }}" class="mt-2 block w-full rounded-xl bg-bmlk-page px-4 py-3 text-base font-normal normal-case tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">
                                    </label>
                                    <label class="block text-sm font-semibold text-bmlk-ink">{{ __('E-mail') }}
                                        <input name="email" type="email" maxlength="255" required autocomplete="email" value="{{ old('mode') === 'sharpening' ? old('email') : auth()->user()?->email }}" placeholder="{{ __('vous@exemple.fr') }}" class="mt-2 block w-full rounded-xl bg-bmlk-page px-4 py-3 text-base font-normal normal-case tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">
                                    </label>
                                </div>
                                <label class="mt-5 flex items-start gap-3 text-xs leading-5 text-[#62565D]">
                                    <input type="checkbox" name="consent" value="1" required @checked(old('mode') === 'sharpening' && old('consent')) class="mt-0.5 size-4 shrink-0 accent-[#712B47]">
                                    <span>{{ __('J’accepte que mes informations soient transmises à l’atelier pour répondre à ma demande.') }}</span>
                                </label>
                                <button type="submit" :disabled="count === 0" class="mt-6 flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-accent px-6 text-sm font-semibold text-white transition hover:bg-bmlk-brand disabled:cursor-not-allowed disabled:opacity-40">
                                    {{ __('Demander l’affûtage') }} <x-lucide-arrow-up-right class="size-4" />
                                </button>
                                <p class="mt-4 text-center text-xs leading-5 text-[#776A70]">{{ __('Demande sans paiement · Notre atelier vous contactera par e-mail.') }}</p>
                            </form>

                            <form x-show="mode === 'diagnostic'" x-cloak method="POST" action="{{ route('reparation.submit') }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="mode" value="diagnostic">
                                <div class="absolute -left-[10000px]" aria-hidden="true"><label>{{ __('Site web') }} <input name="website" tabindex="-1" autocomplete="off"></label></div>
                                <p class="text-xs font-semibold text-accent">{{ __('Diagnostic gratuit avant paiement') }}</p>
                                <h2 class="mt-3 font-serif text-3xl text-bmlk-ink">{{ __('Confiez-nous votre outil') }}</h2>
                                <p class="mt-3 text-sm leading-6 text-[#776A70]">{{ __('Envoyez une photo et une description. Notre artisan vous répond sous 3 jours ouvrés avec les options et le prix exact (5 à 100 €). Le lien de paiement arrive par e-mail et dans votre compte.') }}</p>

                                <div class="mt-7 space-y-5">
                                    <label class="block text-sm font-semibold text-bmlk-ink">{{ __('Outil') }}
                                        <select name="tool" required class="mt-2 block w-full rounded-xl bg-bmlk-page px-4 py-3.5 text-base font-normal normal-case tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">
                                            <option value="scissors" @selected(old('tool') === 'scissors')>{{ __('Ciseaux') }}</option>
                                            <option value="pliers" @selected(old('tool') === 'pliers')>{{ __('Pince à envies') }}</option>
                                            <option value="pusher" @selected(old('tool') === 'pusher')>{{ __('Repousse-peaux') }}</option>
                                            <option value="other" @selected(old('tool') === 'other')>{{ __('Autre outil') }}</option>
                                        </select>
                                    </label>
                                    <label class="block text-sm font-semibold text-bmlk-ink">{{ __('Que se passe-t-il ?') }}
                                        <textarea name="description" required minlength="10" maxlength="2000" rows="4" placeholder="{{ __('Décrivez le problème...') }}" class="mt-2 block w-full resize-y rounded-xl bg-bmlk-page px-4 py-3.5 text-base font-normal normal-case leading-6 tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">{{ old('description') }}</textarea>
                                    </label>
                                    <div>
                                        <label for="repair-photos" class="block text-sm font-semibold text-bmlk-ink">{{ __('Photos') }} <span class="font-normal normal-case text-[#776A70]">{{ __('· jusqu’à 3 images, 4 Mo chacune') }}</span></label>
                                        <label for="repair-photos" class="mt-2 flex min-h-24 cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl bg-bmlk-page px-4 py-5 text-center text-sm text-[#62565D] transition hover:bg-bmlk-soft">
                                            <x-lucide-image-plus class="size-6 text-accent" />
                                            <span>{{ __('Ajouter des photos de l’outil') }}</span>
                                            <span class="text-xs" x-show="photos.length" x-text="photos.join(' · ')"></span>
                                        </label>
                                        <input id="repair-photos" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required class="sr-only" @change="photos = Array.from($event.target.files).map(file => file.name)">
                                    </div>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <label class="block text-sm font-semibold text-bmlk-ink">{{ __('Nom complet') }} <span class="font-normal normal-case text-[#776A70]">{{ __('(facultatif)') }}</span>
                                            <input name="name" type="text" maxlength="100" autocomplete="name" value="{{ old('mode') === 'diagnostic' ? old('name') : auth()->user()?->name }}" placeholder="{{ __('Votre nom') }}" class="mt-2 block w-full rounded-xl bg-bmlk-page px-4 py-3 text-base font-normal normal-case tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">
                                        </label>
                                        <label class="block text-sm font-semibold text-bmlk-ink">{{ __('E-mail') }}
                                            <input name="email" type="email" maxlength="255" required autocomplete="email" value="{{ old('mode') === 'diagnostic' ? old('email') : auth()->user()?->email }}" placeholder="{{ __('vous@exemple.fr') }}" class="mt-2 block w-full rounded-xl bg-bmlk-page px-4 py-3 text-base font-normal normal-case tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">
                                        </label>
                                    </div>
                                </div>
                                <label class="mt-5 flex items-start gap-3 text-xs leading-5 text-[#62565D]">
                                    <input type="checkbox" name="consent" value="1" required @checked(old('mode') === 'diagnostic' && old('consent')) class="mt-0.5 size-4 shrink-0 accent-[#712B47]">
                                    <span>{{ __('J’accepte que mes informations et mes photos soient transmises à l’atelier pour examiner ma demande.') }}</span>
                                </label>
                                <button type="submit" class="mt-6 flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-accent px-6 text-sm font-semibold text-white transition hover:bg-bmlk-brand">{{ __('Demander un diagnostic') }} <x-lucide-arrow-up-right class="size-4" /></button>
                                <p class="mt-4 text-center text-xs leading-5 text-[#776A70]">{{ __('Diagnostic gratuit · Aucun paiement à cette étape.') }}</p>
                            </form>
                        </div>

                        <aside class="grid gap-5">
                            <div class="rounded-3xl bg-white p-5 sm:p-8 lg:p-9">
                                <p class="text-xs font-semibold text-accent">{{ __('Tarifs · par outil') }}</p>
                                <dl class="mt-5 divide-y divide-[#E7CCD5] text-sm text-bmlk-ink">
                                    <div class="flex justify-between gap-4 py-3 first:pt-0"><dt>{{ __('Ciseaux · affûtage') }}</dt><dd class="font-bold">10 €</dd></div>
                                    <div class="flex justify-between gap-4 py-3"><dt>{{ __('Pince · affûtage') }}</dt><dd class="font-bold">10 €</dd></div>
                                    <div class="flex justify-between gap-4 py-3"><dt>{{ __('Repousse-peaux · affûtage') }}</dt><dd class="font-bold">9 €</dd></div>
                                    <div class="flex justify-between gap-4 py-3 last:pb-0"><dt>{{ __('Tout outil · réparation') }}</dt><dd class="text-right font-bold">{{ __('5–100 € après diagnostic') }}</dd></div>
                                </dl>
                            </div>
                            <div class="rounded-3xl bg-[#E7EFF4] p-5 sm:p-8 lg:p-9">
                                <div class="flex items-center gap-3">
                                    <x-lucide-shield-check class="size-5 text-accent" />
                                    <p class="text-xs font-semibold text-accent">{{ __('Garantie') }}</p>
                                </div>
                                <div class="mt-5 space-y-4 text-sm leading-6 text-[#53616C]">
                                    <p>{{ __('Résultat non satisfaisant : nouvel affûtage gratuit sous 30 jours, livraison à nos frais.') }}</p>
                                    <p>{{ __('Colis assurés auprès du transporteur en cas de perte ou de dommage.') }}</p>
                                    <p>{!! __('Droit de rétractation de 14 jours pour les particuliers. Conditions complètes dans les :link.', ['link' => '<a href="'.e(route('page', ['slug' => 'conditions-generales-de-vente'])).'" class="font-semibold underline decoration-[#A77B8B] underline-offset-4 hover:text-accent">'.e(__('CGV')).'</a>']) !!}</p>
                                </div>
                            </div>
                            <div class="rounded-3xl bg-white/60 p-5 sm:p-7">
                                <div class="flex items-start gap-4">
                                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#E9D6DE] text-accent"><x-lucide-package-check class="size-5" /></span>
                                    <p class="text-sm leading-6 text-[#62565D]"><strong class="block text-bmlk-ink">{{ __('Un service pensé pour les pros') }}</strong>{{ __('Expédiez vos outils, notre atelier s’occupe de leur remise en état et de leur retour.') }}</p>
                                </div>
                            </div>
                        </aside>
                    </div>
            </div>
        </section>

        <section class="px-3 pb-4 pt-4 sm:px-5 sm:pb-5 sm:pt-5 lg:px-8 lg:pb-8" id="abonnement" aria-labelledby="subscription-title">
            <div class="relative mx-auto max-w-[1560px] overflow-hidden rounded-3xl bg-accent text-[#FCF8F4]">
                <div class="bmlk-packaging-stripe absolute inset-x-0 top-0" aria-hidden="true"></div>
                <div class="px-5 pb-10 pt-12 sm:px-10 sm:pb-14 sm:pt-16 lg:px-14 lg:py-20 xl:px-16">
                    <p class="font-serif text-2xl italic text-[#E5B8C7] sm:text-3xl" data-reveal data-reveal-y="10">{{ __('abonnement') }}</p>
                    <h2 id="subscription-title" class="mt-2 font-serif text-4xl font-medium leading-tight tracking-tight sm:text-5xl lg:text-6xl" data-reveal-title data-reveal-title-duration="1">{{ __('4 affûtages') }} <span class="italic text-[#E4C0CB]">{{ __('par an') }}</span></h2>
                    <p class="mt-4 max-w-3xl text-sm leading-7 text-[#F0DCE2] sm:text-base">{{ __('Un outil affûté tous les 3 mois coupe parfaitement et dure des années. Livraison aller-retour incluse les 4 fois, −20 % sur les réparations, rappel « C’est l’heure de l’affûtage » avec étiquette prête, priorité à l’atelier.') }}</p>

                    <div class="mt-9 grid gap-4 lg:grid-cols-3 lg:gap-5" data-blur-reveal data-blur-stagger="0.1" data-blur-duration="0.8" data-blur-y="18" data-blur-pixels="7">
                        @foreach ([['start', 'Start', 119, 3, '9,90', '170', '30'], ['master', 'Master', 199, 6, '16,60', '290', '31'], ['salon', 'Salon', 349, 12, '29', '480', '27']] as [$id, $name, $cost, $tools, $monthly, $normal, $saving])
                            <article class="relative flex flex-col rounded-3xl p-6 text-bmlk-ink sm:p-8 {{ $id === 'master' ? 'bg-bmlk-soft' : 'bg-white' }}" data-blur-reveal-item>
                                @if ($id === 'master')
                                    <span class="absolute -top-3 right-5 rounded-full bg-[#E4C0CB] px-4 py-2 text-[10px] font-semibold text-accent">{{ __('Le plus avantageux') }}</span>
                                @endif
                                <p class="text-sm font-semibold text-accent">{{ $name }}</p>
                                <div class="mt-3 flex items-baseline gap-1"><strong class="font-serif text-5xl font-normal tracking-tight text-accent sm:text-6xl">{{ $cost }} €</strong><span class="text-sm text-bmlk-ink">{{ __('/ an') }}</span></div>
                                <p class="mt-5 text-sm">{{ __('Jusqu’à :tools outils × 4 affûtages', ['tools' => $tools]) }}</p>
                                <p class="mt-3 text-sm text-[#776A70]">{{ __('≈ :monthly € / mois', ['monthly' => $monthly]) }}</p>
                                <p class="mt-4 border-t border-[#E3C8D1] pt-4 text-sm text-[#776A70]">{{ __('Au tarif normal ≈ :normal € · économie ~:saving %', ['normal' => $normal, 'saving' => $saving]) }}</p>
                                <div class="mt-auto pt-6">
                                    <button type="button" @click="plan = '{{ $id }}'; subscriptionOpen = true; $nextTick(() => $refs.subscriptionForm.scrollIntoView({ behavior: 'smooth', block: 'center' }))" class="flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-accent px-5 text-sm font-semibold text-white transition hover:bg-bmlk-brand">{{ __('S’abonner') }} <x-lucide-arrow-up-right class="size-4" /></button>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div x-show="subscriptionOpen" x-cloak x-ref="subscriptionForm" id="subscription-request" class="mx-auto mt-7 max-w-2xl rounded-3xl bg-white p-6 text-bmlk-ink sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold text-accent">{{ __('Votre abonnement') }}</p>
                                <h3 class="mt-2 font-serif text-3xl">{{ __('Demander une souscription') }}</h3>
                            </div>
                            <button type="button" @click="subscriptionOpen = false" class="flex size-9 shrink-0 items-center justify-center rounded-full bg-bmlk-page text-accent" aria-label="{{ __('Fermer') }}"><x-lucide-x class="size-4" /></button>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-[#776A70]">{{ __('La souscription n’est pas encore facturée en ligne. Notre équipe vous recontactera pour l’activation.') }}</p>
                        <form method="POST" action="{{ route('reparation.submit') }}" class="mt-5 space-y-4">
                            @csrf
                            <input type="hidden" name="mode" value="subscription">
                            <input type="hidden" name="plan" :value="plan">
                            <div class="absolute -left-[10000px]" aria-hidden="true"><label>{{ __('Site web') }} <input name="website" tabindex="-1" autocomplete="off"></label></div>
                            <p class="rounded-xl bg-bmlk-page px-4 py-3 text-sm">{{ __('Formule sélectionnée :') }} <strong class="capitalize" x-text="plan"></strong></p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="block text-sm font-semibold text-bmlk-ink">{{ __('Nom complet') }} <span class="font-normal normal-case text-[#776A70]">{{ __('(facultatif)') }}</span>
                                    <input name="name" type="text" maxlength="100" autocomplete="name" value="{{ old('mode') === 'subscription' ? old('name') : auth()->user()?->name }}" class="mt-2 block w-full rounded-xl bg-bmlk-page px-4 py-3 text-base font-normal normal-case tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">
                                </label>
                                <label class="block text-sm font-semibold text-bmlk-ink">{{ __('E-mail') }}
                                    <input name="email" type="email" maxlength="255" required autocomplete="email" value="{{ old('mode') === 'subscription' ? old('email') : auth()->user()?->email }}" placeholder="{{ __('vous@exemple.fr') }}" class="mt-2 block w-full rounded-xl bg-bmlk-page px-4 py-3 text-base font-normal normal-case tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">
                                </label>
                            </div>
                            <label class="flex items-start gap-3 text-xs leading-5 text-[#62565D]">
                                <input type="checkbox" name="consent" value="1" required @checked(old('mode') === 'subscription' && old('consent')) class="mt-0.5 size-4 shrink-0 accent-[#712B47]">
                                <span>{{ __('J’accepte que mes informations soient transmises à l’atelier pour répondre à ma demande.') }}</span>
                            </label>
                            <button type="submit" class="min-h-12 w-full rounded-full bg-accent px-5 text-sm font-semibold text-white transition hover:bg-bmlk-brand">{{ __('Envoyer ma demande') }}</button>
                        </form>
                    </div>

                    <p class="mt-9 max-w-4xl text-xs leading-6 text-[#E9CED7]">{{ __('Rétractation sous 14 jours : remboursement déduction faite des affûtages réalisés. Rappel par e-mail avant chaque renouvellement, résiliation en ligne en quelques clics depuis votre compte.') }} <span class="font-semibold">{{ __('Ces fonctionnalités seront disponibles lors de l’activation des abonnements.') }}</span></p>
                </div>
            </div>
        </section>
    </main>
</x-layout>
