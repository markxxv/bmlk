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
        <section class="px-3 pt-4 sm:px-5 sm:pt-6 lg:px-8" aria-labelledby="repair-title">
            <div class="mx-auto max-w-[1560px] overflow-hidden rounded-3xl bg-[#F5E7EB]">
                <div class="px-5 pb-10 pt-9 sm:px-10 sm:pb-14 sm:pt-12 lg:px-14 lg:pb-16 lg:pt-16 xl:px-16">
                    <div class="max-w-4xl">
                        <p class="font-serif text-2xl italic text-bmlk-brand sm:text-3xl" data-reveal data-reveal-y="10">réparation</p>
                        <h1 id="repair-title" class="mt-2 font-serif text-[2.5rem] font-medium uppercase leading-[1.02] tracking-tight text-bmlk-ink sm:text-6xl lg:text-7xl xl:text-[5.5rem]" data-reveal-title data-reveal-title-duration="1">
                            Réparation <span class="italic text-bmlk-brand">d’outils</span>
                        </h1>
                        <p class="mt-5 max-w-2xl text-base leading-7 text-[#604650] sm:text-lg sm:leading-8" data-reveal data-reveal-y="12" data-reveal-delay="0.1">
                            Affûtage et remise en état de vos ciseaux, pinces et repousse-peaux. Vous envoyez, notre atelier répare et vous renvoie vos outils.
                        </p>
                    </div>

                    @if (session('success'))
                        <div role="status" class="mt-7 max-w-3xl rounded-2xl bg-white px-5 py-4 text-sm font-medium text-bmlk-brand">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div role="alert" class="mt-7 max-w-3xl rounded-2xl bg-white px-5 py-4 text-sm text-red-700">{{ $errors->first() }}</div>
                    @endif

                    <div class="mt-8 flex flex-wrap gap-2 sm:mt-9" role="group" aria-label="Type de prestation" data-reveal data-reveal-y="12" data-reveal-delay="0.15">
                        <button type="button" @click="mode = 'sharpening'" :aria-pressed="mode === 'sharpening'" :class="mode === 'sharpening' ? 'bg-bmlk-brand text-white' : 'bg-white/70 text-bmlk-brand hover:bg-white'" class="min-h-12 rounded-full px-6 py-3 text-[11px] font-bold uppercase tracking-[0.14em] transition-colors sm:px-7">Affûtage</button>
                        <button type="button" @click="mode = 'diagnostic'" :aria-pressed="mode === 'diagnostic'" :class="mode === 'diagnostic' ? 'bg-bmlk-brand text-white' : 'bg-white/70 text-bmlk-brand hover:bg-white'" class="min-h-12 rounded-full px-6 py-3 text-[11px] font-bold uppercase tracking-[0.12em] transition-colors sm:px-7">Réparation · diagnostic gratuit</button>
                    </div>

                    <div class="mt-5 grid items-start gap-5 lg:grid-cols-2 lg:gap-6">
                        <div class="min-w-0 rounded-3xl bg-[#FCF8F4] p-5 sm:p-8 lg:p-9">
                            <form x-show="mode === 'sharpening'" method="POST" action="{{ route('reparation.submit') }}" x-cloak>
                                @csrf
                                <input type="hidden" name="mode" value="sharpening">
                                <div class="absolute -left-[10000px]" aria-hidden="true"><label>Site web <input name="website" tabindex="-1" autocomplete="off"></label></div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-bmlk-brand">Étape 1 · Vos outils</p>
                                <h2 class="mt-3 font-serif text-3xl text-bmlk-ink">Préparez votre affûtage</h2>
                                <p class="mt-2 text-sm leading-6 text-[#715C65]">Sélectionnez les quantités. Notre atelier vous confirmera l’envoi et le règlement.</p>

                                <div class="mt-7 divide-y divide-[#E7CCD5]">
                                    @foreach ([['Ciseaux', 'scissors', 10], ['Pince', 'pliers', 10], ['Repousse-peaux', 'pushers', 9]] as [$label, $key, $price])
                                        <div class="flex items-center gap-3 py-4 first:pt-0 sm:gap-5">
                                            <div class="min-w-0 flex-1">
                                                <p class="font-serif text-xl text-bmlk-ink sm:text-2xl">{{ $label }}</p>
                                                <p class="text-xs text-[#715C65]">Affûtage · {{ $price }} € / outil</p>
                                            </div>
                                            <div class="flex shrink-0 items-center rounded-full bg-[#F3E8EC]">
                                                <button type="button" @click="change('{{ $key }}', -1)" class="flex size-10 items-center justify-center rounded-full text-bmlk-brand transition hover:bg-[#EAD7DE]" aria-label="Retirer un {{ strtolower($label) }}"><x-lucide-minus class="size-4" /></button>
                                                <output class="w-7 text-center text-sm font-semibold tabular-nums text-bmlk-ink" x-text="{{ $key }}">0</output>
                                                <button type="button" @click="change('{{ $key }}', 1)" class="flex size-10 items-center justify-center rounded-full text-bmlk-brand transition hover:bg-[#EAD7DE]" aria-label="Ajouter un {{ strtolower($label) }}"><x-lucide-plus class="size-4" /></button>
                                            </div>
                                            <p class="w-14 shrink-0 text-right font-serif text-xl tabular-nums text-bmlk-brand sm:w-16" x-text="({{ $key }} * {{ $price }}) + ' €'">0 €</p>
                                            <input type="hidden" name="{{ $key }}" :value="{{ $key }}">
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-2 rounded-2xl bg-[#E0EBF0] px-4 py-4" aria-live="polite">
                                    <p class="text-xs font-semibold leading-5 text-[#34414B]" x-text="count >= 10 ? 'Livraison aller-retour offerte pour votre sélection' : 'Ajoutez encore ' + (10 - count) + ' outil' + (10 - count > 1 ? 's' : '') + ' : la livraison aller-retour sera offerte'"></p>
                                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/80">
                                        <div class="h-full rounded-full bg-bmlk-brand transition-all" :style="'width: ' + Math.min(100, count * 10) + '%'"></div>
                                    </div>
                                </div>
                                <div class="mt-5 flex items-center justify-between gap-4 border-b border-[#DCC2CA] pb-5 text-xs text-[#715C65]">
                                    <span>Livraison aller-retour</span>
                                    <span class="text-right font-medium text-bmlk-ink" x-text="count >= 10 ? 'Offerte' : 'Calculée par le transporteur'"></span>
                                </div>
                                <div class="mt-5 flex items-end justify-between gap-3">
                                    <span class="text-[11px] font-bold uppercase tracking-[0.17em] text-bmlk-ink">Total indicatif</span>
                                    <strong class="font-serif text-3xl font-normal text-bmlk-brand sm:text-4xl" x-text="amount + ' €'">0 €</strong>
                                </div>
                                <p class="mt-2 text-right text-xs text-[#715C65]">Hors frais d’expédition éventuels</p>

                                <div class="mt-7 grid gap-4 sm:grid-cols-2">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-bmlk-ink">Nom <span class="font-normal normal-case text-[#715C65]">(facultatif)</span>
                                        <input name="name" type="text" maxlength="100" autocomplete="name" value="{{ old('mode') === 'sharpening' ? old('name') : auth()->user()?->name }}" placeholder="Votre nom" class="mt-2 block w-full rounded-xl bg-[#F2E8EC] px-4 py-3 text-base font-normal normal-case tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">
                                    </label>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-bmlk-ink">E-mail
                                        <input name="email" type="email" maxlength="255" required autocomplete="email" value="{{ old('mode') === 'sharpening' ? old('email') : auth()->user()?->email }}" placeholder="vous@exemple.fr" class="mt-2 block w-full rounded-xl bg-[#F2E8EC] px-4 py-3 text-base font-normal normal-case tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">
                                    </label>
                                </div>
                                <label class="mt-5 flex items-start gap-3 text-xs leading-5 text-[#604650]">
                                    <input type="checkbox" name="consent" value="1" required @checked(old('mode') === 'sharpening' && old('consent')) class="mt-0.5 size-4 shrink-0 accent-[#712B47]">
                                    <span>J’accepte que mes informations soient transmises à l’atelier pour répondre à ma demande.</span>
                                </label>
                                <button type="submit" :disabled="count === 0" class="mt-6 flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-bmlk-brand px-6 text-xs font-bold uppercase tracking-[0.13em] text-white transition hover:bg-[#572039] disabled:cursor-not-allowed disabled:opacity-40">
                                    Demander l’affûtage <x-lucide-arrow-up-right class="size-4" />
                                </button>
                                <p class="mt-4 text-center text-xs leading-5 text-[#715C65]">Demande sans paiement · Notre atelier vous contactera par e-mail.</p>
                            </form>

                            <form x-show="mode === 'diagnostic'" x-cloak method="POST" action="{{ route('reparation.submit') }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="mode" value="diagnostic">
                                <div class="absolute -left-[10000px]" aria-hidden="true"><label>Site web <input name="website" tabindex="-1" autocomplete="off"></label></div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-bmlk-brand">Diagnostic gratuit avant paiement</p>
                                <h2 class="mt-3 font-serif text-3xl text-bmlk-ink">Confiez-nous votre outil</h2>
                                <p class="mt-3 text-sm leading-6 text-[#715C65]">Envoyez une photo et une description. Notre artisan vous répond sous 3 jours ouvrés avec les options et le prix exact (5 à 100 €). Le lien de paiement arrive par e-mail et dans votre compte.</p>

                                <div class="mt-7 space-y-5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-bmlk-ink">Outil
                                        <select name="tool" required class="mt-2 block w-full rounded-xl bg-[#F2E8EC] px-4 py-3.5 text-base font-normal normal-case tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">
                                            <option value="scissors" @selected(old('tool') === 'scissors')>Ciseaux</option>
                                            <option value="pliers" @selected(old('tool') === 'pliers')>Pince à envies</option>
                                            <option value="pusher" @selected(old('tool') === 'pusher')>Repousse-peaux</option>
                                            <option value="other" @selected(old('tool') === 'other')>Autre outil</option>
                                        </select>
                                    </label>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-bmlk-ink">Que se passe-t-il ?
                                        <textarea name="description" required minlength="10" maxlength="2000" rows="4" placeholder="Décrivez le problème..." class="mt-2 block w-full resize-y rounded-xl bg-[#F2E8EC] px-4 py-3.5 text-base font-normal normal-case leading-6 tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">{{ old('description') }}</textarea>
                                    </label>
                                    <div>
                                        <label for="repair-photos" class="block text-xs font-bold uppercase tracking-wider text-bmlk-ink">Photos <span class="font-normal normal-case text-[#715C65]">· jusqu’à 3 images, 4 Mo chacune</span></label>
                                        <label for="repair-photos" class="mt-2 flex min-h-24 cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl bg-[#F2E8EC] px-4 py-5 text-center text-sm text-[#604650] transition hover:bg-[#EADBE1]">
                                            <x-lucide-image-plus class="size-6 text-bmlk-brand" />
                                            <span>Ajouter des photos de l’outil</span>
                                            <span class="text-xs" x-show="photos.length" x-text="photos.join(' · ')"></span>
                                        </label>
                                        <input id="repair-photos" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required class="sr-only" @change="photos = Array.from($event.target.files).map(file => file.name)">
                                    </div>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-bmlk-ink">Nom <span class="font-normal normal-case text-[#715C65]">(facultatif)</span>
                                            <input name="name" type="text" maxlength="100" autocomplete="name" value="{{ old('mode') === 'diagnostic' ? old('name') : auth()->user()?->name }}" placeholder="Votre nom" class="mt-2 block w-full rounded-xl bg-[#F2E8EC] px-4 py-3 text-base font-normal normal-case tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">
                                        </label>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-bmlk-ink">E-mail
                                            <input name="email" type="email" maxlength="255" required autocomplete="email" value="{{ old('mode') === 'diagnostic' ? old('email') : auth()->user()?->email }}" placeholder="vous@exemple.fr" class="mt-2 block w-full rounded-xl bg-[#F2E8EC] px-4 py-3 text-base font-normal normal-case tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">
                                        </label>
                                    </div>
                                </div>
                                <label class="mt-5 flex items-start gap-3 text-xs leading-5 text-[#604650]">
                                    <input type="checkbox" name="consent" value="1" required @checked(old('mode') === 'diagnostic' && old('consent')) class="mt-0.5 size-4 shrink-0 accent-[#712B47]">
                                    <span>J’accepte que mes informations et mes photos soient transmises à l’atelier pour examiner ma demande.</span>
                                </label>
                                <button type="submit" class="mt-6 flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-bmlk-brand px-6 text-xs font-bold uppercase tracking-[0.13em] text-white transition hover:bg-[#572039]">Demander un diagnostic <x-lucide-arrow-up-right class="size-4" /></button>
                                <p class="mt-4 text-center text-xs leading-5 text-[#715C65]">Diagnostic gratuit · Aucun paiement à cette étape.</p>
                            </form>
                        </div>

                        <aside class="grid gap-5">
                            <div class="rounded-3xl bg-[#FCF8F4] p-5 sm:p-8 lg:p-9">
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-bmlk-brand">Tarifs · par outil</p>
                                <dl class="mt-5 divide-y divide-[#E7CCD5] text-sm text-bmlk-ink">
                                    <div class="flex justify-between gap-4 py-3 first:pt-0"><dt>Ciseaux · affûtage</dt><dd class="font-bold">10 €</dd></div>
                                    <div class="flex justify-between gap-4 py-3"><dt>Pince · affûtage</dt><dd class="font-bold">10 €</dd></div>
                                    <div class="flex justify-between gap-4 py-3"><dt>Repousse-peaux · affûtage</dt><dd class="font-bold">9 €</dd></div>
                                    <div class="flex justify-between gap-4 py-3 last:pb-0"><dt>Tout outil · réparation</dt><dd class="text-right font-bold">5–100 € après diagnostic</dd></div>
                                </dl>
                            </div>
                            <div class="rounded-3xl bg-[#DFEBF0] p-5 sm:p-8 lg:p-9">
                                <div class="flex items-center gap-3">
                                    <x-lucide-shield-check class="size-5 text-bmlk-brand" />
                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-bmlk-brand">Garantie</p>
                                </div>
                                <div class="mt-5 space-y-4 text-sm leading-6 text-[#45505A]">
                                    <p>Résultat non satisfaisant : nouvel affûtage gratuit sous 30 jours, livraison à nos frais.</p>
                                    <p>Colis assurés auprès du transporteur en cas de perte ou de dommage.</p>
                                    <p>Droit de rétractation de 14 jours pour les particuliers. Conditions complètes dans les <a href="/conditions-generales-de-vente" class="font-semibold underline decoration-[#A77B8B] underline-offset-4 hover:text-bmlk-brand">CGV</a>.</p>
                                </div>
                            </div>
                            <div class="rounded-3xl bg-white/60 p-5 sm:p-7">
                                <div class="flex items-start gap-4">
                                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#E9D6DE] text-bmlk-brand"><x-lucide-package-check class="size-5" /></span>
                                    <p class="text-sm leading-6 text-[#604650]"><strong class="block text-bmlk-ink">Un service pensé pour les pros</strong>Expédiez vos outils, notre atelier s’occupe de leur remise en état et de leur retour.</p>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </section>

        <section class="px-3 pb-4 pt-4 sm:px-5 sm:pb-5 sm:pt-5 lg:px-8 lg:pb-8" id="abonnement" aria-labelledby="subscription-title">
            <div class="relative mx-auto max-w-[1560px] overflow-hidden rounded-3xl bg-[#712B47] text-[#FCF8F4]">
                <div class="bmlk-packaging-stripe absolute inset-x-0 top-0" aria-hidden="true"></div>
                <div class="px-5 pb-10 pt-12 sm:px-10 sm:pb-14 sm:pt-16 lg:px-14 lg:py-20 xl:px-16">
                    <p class="font-serif text-2xl italic text-[#E5B8C7] sm:text-3xl" data-reveal data-reveal-y="10">abonnement</p>
                    <h2 id="subscription-title" class="mt-2 font-serif text-4xl font-medium uppercase leading-tight tracking-tight sm:text-5xl lg:text-6xl" data-reveal-title data-reveal-title-duration="1">4 affûtages <span class="italic">par an</span></h2>
                    <p class="mt-4 max-w-3xl text-sm leading-7 text-[#F0DCE2] sm:text-base">Un outil affûté tous les 3 mois coupe parfaitement et dure des années. Livraison aller-retour incluse les 4 fois, −20 % sur les réparations, rappel « C’est l’heure de l’affûtage » avec étiquette prête, priorité à l’atelier.</p>

                    <div class="mt-9 grid gap-4 lg:grid-cols-3 lg:gap-5" data-blur-reveal data-blur-stagger="0.1" data-blur-duration="0.8" data-blur-y="18" data-blur-pixels="7">
                        @foreach ([['start', 'Start', 119, 3, '9,90', '170', '30'], ['master', 'Master', 199, 6, '16,60', '290', '31'], ['salon', 'Salon', 349, 12, '29', '480', '27']] as [$id, $name, $cost, $tools, $monthly, $normal, $saving])
                            <article class="relative flex flex-col rounded-3xl p-6 text-bmlk-ink sm:p-8 {{ $id === 'master' ? 'bg-[#F1E0E6]' : 'bg-[#FCF8F4]' }}" data-blur-reveal-item>
                                @if ($id === 'master')
                                    <span class="absolute -top-3 right-5 rounded-full bg-[#D7A9B9] px-4 py-2 text-[10px] font-bold uppercase tracking-[0.14em] text-[#572039]">Le plus avantageux</span>
                                @endif
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-bmlk-brand">{{ $name }}</p>
                                <div class="mt-3 flex items-baseline gap-1"><strong class="font-serif text-5xl font-normal tracking-tight text-bmlk-brand sm:text-6xl">{{ $cost }} €</strong><span class="text-sm text-bmlk-ink">/ an</span></div>
                                <p class="mt-5 text-sm">Jusqu’à {{ $tools }} outils × 4 affûtages</p>
                                <p class="mt-3 text-sm text-[#715C65]">≈ {{ $monthly }} € / mois</p>
                                <p class="mt-4 border-t border-[#E3C8D1] pt-4 text-sm text-[#715C65]">Au tarif normal ≈ {{ $normal }} € · économie ~{{ $saving }} %</p>
                                <div class="mt-auto pt-6">
                                    <button type="button" @click="plan = '{{ $id }}'; subscriptionOpen = true; $nextTick(() => $refs.subscriptionForm.scrollIntoView({ behavior: 'smooth', block: 'center' }))" class="flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-bmlk-brand px-5 text-xs font-bold uppercase tracking-[0.13em] text-white transition hover:bg-[#572039]">S’abonner <x-lucide-arrow-up-right class="size-4" /></button>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div x-show="subscriptionOpen" x-cloak x-ref="subscriptionForm" id="subscription-request" class="mx-auto mt-7 max-w-2xl rounded-3xl bg-[#FCF8F4] p-6 text-bmlk-ink sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-bmlk-brand">Votre abonnement</p>
                                <h3 class="mt-2 font-serif text-3xl">Demander une souscription</h3>
                            </div>
                            <button type="button" @click="subscriptionOpen = false" class="flex size-9 shrink-0 items-center justify-center rounded-full bg-[#F2E8EC] text-bmlk-brand" aria-label="Fermer"><x-lucide-x class="size-4" /></button>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-[#715C65]">La souscription n’est pas encore facturée en ligne. Notre équipe vous recontactera pour l’activation.</p>
                        <form method="POST" action="{{ route('reparation.submit') }}" class="mt-5 space-y-4">
                            @csrf
                            <input type="hidden" name="mode" value="subscription">
                            <input type="hidden" name="plan" :value="plan">
                            <div class="absolute -left-[10000px]" aria-hidden="true"><label>Site web <input name="website" tabindex="-1" autocomplete="off"></label></div>
                            <p class="rounded-xl bg-[#F2E8EC] px-4 py-3 text-sm">Formule sélectionnée : <strong class="capitalize" x-text="plan"></strong></p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-bmlk-ink">Nom <span class="font-normal normal-case text-[#715C65]">(facultatif)</span>
                                    <input name="name" type="text" maxlength="100" autocomplete="name" value="{{ old('mode') === 'subscription' ? old('name') : auth()->user()?->name }}" class="mt-2 block w-full rounded-xl bg-[#F2E8EC] px-4 py-3 text-base font-normal normal-case tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">
                                </label>
                                <label class="block text-xs font-bold uppercase tracking-wider text-bmlk-ink">E-mail
                                    <input name="email" type="email" maxlength="255" required autocomplete="email" value="{{ old('mode') === 'subscription' ? old('email') : auth()->user()?->email }}" placeholder="vous@exemple.fr" class="mt-2 block w-full rounded-xl bg-[#F2E8EC] px-4 py-3 text-base font-normal normal-case tracking-normal outline-none focus:ring-2 focus:ring-bmlk-brand">
                                </label>
                            </div>
                            <label class="flex items-start gap-3 text-xs leading-5 text-[#604650]">
                                <input type="checkbox" name="consent" value="1" required @checked(old('mode') === 'subscription' && old('consent')) class="mt-0.5 size-4 shrink-0 accent-[#712B47]">
                                <span>J’accepte que mes informations soient transmises à l’atelier pour répondre à ma demande.</span>
                            </label>
                            <button type="submit" class="min-h-12 w-full rounded-full bg-bmlk-brand px-5 text-xs font-bold uppercase tracking-[0.13em] text-white transition hover:bg-[#572039]">Envoyer ma demande</button>
                        </form>
                    </div>

                    <p class="mt-9 max-w-4xl text-xs leading-6 text-[#E9CED7]">Rétractation sous 14 jours : remboursement déduction faite des affûtages réalisés. Rappel par e-mail avant chaque renouvellement, résiliation en ligne en quelques clics depuis votre compte. <span class="font-semibold">Ces fonctionnalités seront disponibles lors de l’activation des abonnements.</span></p>
                </div>
            </div>
        </section>
    </main>
</x-layout>
