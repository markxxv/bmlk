@push('head')
    <link rel="stylesheet" href="{{ asset('css/createurs.css') }}?v=6">
@endpush

<x-layout
    :title="__('Programme créatrices | BLACK MILK')"
    :meta-description="__('Rejoignez les créatrices BLACK MILK. Découvrez notre programme et envoyez votre candidature en quelques minutes.')"
    :canonical="route('createurs')"
    :og-image="asset('img/createurs-modele-cire.jpg')"
>
    <main class="bmc" id="createurs">
        <section class="bmc-hero" aria-labelledby="bmc-hero-title">
            <div class="bmc-wrap bmc-hero__inner">
                <div class="bmc-hero__text">
                    <p class="bmc-kicker" data-reveal data-reveal-y="10" data-reveal-duration="0.7" data-reveal-once="true">{{ __('Programme créatrices') }}</p>
                    <h1 id="bmc-hero-title" class="bmc-display bmc-hero-title" data-reveal-title data-reveal-title-duration="1.05" data-reveal-title-stagger="0.09" data-reveal-title-once="true">
                        <span class="bmc-hero-title__first">{{ __('Testez') }} <span>{{ __('Créez') }}</span></span>
                        <span class="bmc-hero-title__second">{{ __('Partagez') }} <em class="bmc-hero-title__signature">{{ __('avec nous') }}</em></span>
                    </h1>
                    <p class="bmc-lead bmc-muted" data-reveal data-reveal-y="16" data-reveal-duration="0.85" data-reveal-delay="0.14" data-reveal-once="true">{{ __('Prothésistes ongulaires, créatrices UGC, passionnées de beauté des ongles : partagez votre univers avec BLACK MILK et rejoignez notre communauté.') }}</p>
                    <div class="bmc-row" data-blur-reveal data-blur-duration="0.75" data-blur-delay="0.24" data-blur-y="14" data-blur-pixels="7" data-blur-once="true">
                        <a class="bmc-btn" href="#candidature">{{ __('Je candidate') }}</a>
                        <span class="bmc-meta bmc-muted">Une demande simple · Quelques minutes</span>
                    </div>
                </div>
                <div class="bmc-hero__visual" aria-hidden="true">
                    <div class="bmc-arch">
                        <img src="{{ asset('img/createurs-modele-cire.jpg') }}" alt="{{ __('Créatrice BLACK MILK') }}" width="380" height="500" fetchpriority="high" data-image-reveal="bottom" data-image-reveal-duration="1.1" data-image-reveal-once="true">
                        <div class="bmc-seal">
                            <img class="bmc-seal__ring" src="{{ asset('img/createurs-seal-ring.svg') }}" width="200" height="200" alt="" decoding="async" data-rotating-seal>
                            <div class="bmc-seal__core"><span>BM</span><span aria-hidden="true">✳</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bmc-steps" aria-labelledby="bmc-steps-title">
            <div class="bmc-wrap">
                <div class="bmc-steps__head">
                    <h2 id="bmc-steps-title" class="bmc-display" data-reveal-title data-reveal-title-duration="1" data-reveal-title-once="true">{{ __('Comment ça marche') }}</h2>
                    <span aria-hidden="true" class="text-3xl">✳</span>
                </div>
                <ol class="bmc-steps__grid" data-blur-reveal data-blur-stagger="0.1" data-blur-duration="0.8" data-blur-y="20" data-blur-pixels="8" data-blur-once="true">
                    <li class="bmc-step" data-blur-reveal-item>
                        <span class="bmc-step__num" aria-hidden="true">01</span>
                        <span class="bmc-step__cap">{{ __('Candidatez') }}</span>
                        <p>{{ __('Présentez-vous et partagez votre profil. Notre équipe étudie votre demande.') }}</p>
                    </li>
                    <li class="bmc-step" data-blur-reveal-item>
                        <span class="bmc-step__num" aria-hidden="true">02</span>
                        <span class="bmc-step__cap">{{ __('Rejoignez-nous') }}</span>
                        <p>{{ __('Une fois votre candidature approuvée, accédez à votre espace personnel.') }}</p>
                    </li>
                    <li class="bmc-step" data-blur-reveal-item>
                        <span class="bmc-step__num" aria-hidden="true">03</span>
                        <span class="bmc-step__cap">{{ __('Partagez') }}</span>
                        <p>{{ __('Recevez votre code de réduction personnel et partagez-le avec votre communauté.') }}</p>
                    </li>
                </ol>
            </div>
        </section>

        <section class="bmc-terms" aria-labelledby="bmc-benefits-title">
            <div class="bmc-wrap bmc-terms__grid" data-blur-reveal data-blur-stagger="0.12" data-blur-duration="0.8" data-blur-y="18" data-blur-pixels="7" data-blur-once="true">
                <div class="bmc-card" data-blur-reveal-item>
                    <h2 id="bmc-benefits-title" class="bmc-display">{{ __('Pour vous') }}</h2>
                    <ul class="bmc-list">
                        <li><span aria-hidden="true">✳</span><span>{{ __('Un code de réduction personnel pour votre communauté') }}</span></li>
                        <li><span aria-hidden="true">✳</span><span>{{ __('Des opportunités de collaboration avec BLACK MILK') }}</span></li>
                        <li><span aria-hidden="true">✳</span><span>{{ __('Un espace personnel pour retrouver votre code') }}</span></li>
                    </ul>
                </div>
                <div class="bmc-card bmc-card--soft" data-blur-reveal-item>
                    <h2 class="bmc-display">{{ __('Pour nous') }}</h2>
                    <ul class="bmc-list">
                        <li><span aria-hidden="true">✳</span><span>{{ __('Votre regard sincère sur les produits') }}</span></li>
                        <li><span aria-hidden="true">✳</span><span>{{ __('Des contenus authentiques, à votre image') }}</span></li>
                        <li><span aria-hidden="true">✳</span><span>{{ __('Une communauté passionnée par la beauté des ongles') }}</span></li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="bmc-apply" id="candidature" aria-labelledby="bmc-form-title">
            <div class="bmc-wrap">
                <div class="bmc-form">
                    <h2 id="bmc-form-title" class="bmc-display" data-blur-reveal data-blur-duration="0.8" data-blur-y="14" data-blur-pixels="6" data-blur-once="true">Votre<span class="bmc-script">candidature</span></h2>
                    <p class="bmc-lead bmc-muted" data-reveal data-reveal-y="12" data-reveal-duration="0.75" data-reveal-once="true">Quelques informations suffisent pour faire connaissance.</p>

                    @if (session('success'))
                        <p class="bmc-alert" role="status">{{ session('success') }}</p>
                    @endif
                    @if ($errors->any())
                        <p class="bmc-alert" role="alert">{{ $errors->first() }}</p>
                    @endif

                    @if ($user?->is_influencer && $user->hasVerifiedEmail())
                        <div class="bmc-alert">
                            {{ __('Votre candidature a déjà été approuvée.') }}
                        </div>
                        <a class="bmc-btn" href="{{ route('account.influencer') }}">Ouvrir mon espace</a>
                    @elseif ($application?->status === 'pending')
                        <div class="bmc-alert">
                            {{ __('Votre candidature est en cours d’examen.') }}
                            @if (! $user->hasVerifiedEmail())
                                {{ __('Confirmez votre e-mail pour accéder à votre espace.') }}
                            @endif
                        </div>
                        @if (! $user->hasVerifiedEmail())
                            <form method="POST" action="{{ route('verification.send') }}">
                                @csrf
                                <button type="submit" class="bmc-btn bmc-btn--line">Renvoyer l’e-mail</button>
                            </form>
                        @endif
                        <a class="bmc-link" href="{{ route('account.roles') }}">Voir ma candidature</a>
                    @elseif ($user && ! $user->hasVerifiedEmail())
                        <div class="bmc-alert">Confirmez votre e-mail avant d’envoyer votre candidature.</div>
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="bmc-btn bmc-btn--line">Renvoyer l’e-mail</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('createurs.apply') }}" class="flex flex-col gap-4 sm:gap-6">
                            @csrf
                            <div class="bmc-hp" aria-hidden="true">
                                <label>Site web<input name="website" type="text" tabindex="-1" autocomplete="off"></label>
                            </div>
                            <div class="bmc-grid2">
                                <label class="bmc-field">
                                    Votre nom
                                    <input name="name" type="text" required minlength="2" maxlength="100" autocomplete="name" placeholder="Camille Martin" value="{{ old('name', $user?->name) }}" @if($user) readonly @endif>
                                </label>
                                <label class="bmc-field">
                                    Votre e-mail
                                    <input name="email" type="email" required maxlength="255" autocomplete="email" placeholder="camille@exemple.fr" value="{{ old('email', $user?->email) }}" @if($user) readonly @endif>
                                </label>
                            </div>
                            <label class="bmc-field">
                                Instagram, TikTok ou autre profil
                                <input name="profile" type="text" required minlength="3" maxlength="255" autocomplete="url" placeholder="@camille.nails" value="{{ old('profile') }}">
                            </label>
                            <label class="bmc-field">
                                Un mot sur vous <span class="bmc-hint">(facultatif)</span>
                                <textarea name="message" maxlength="800" rows="3" placeholder="Votre univers et ce que vous aimeriez partager…">{{ old('message') }}</textarea>
                            </label>
                            <label class="bmc-check">
                                <input type="checkbox" name="major" value="1" required @checked(old('major'))>
                                <span>J’ai 18 ans ou plus.</span>
                            </label>
                            <label class="bmc-check">
                                <input type="checkbox" name="consent" value="1" required @checked(old('consent'))>
                                <span>{{ __('J’accepte que BLACK MILK utilise ces informations pour examiner ma candidature et me recontacter.') }}</span>
                            </label>
                            <p class="bmc-legal">{{ __('Votre candidature sera enregistrée dans votre espace personnel. Un e-mail de confirmation vous sera envoyé si vous créez votre compte.') }}</p>
                            <button class="bmc-btn" type="submit">{{ __('Envoyer ma candidature') }}</button>
                            @guest
                                <p class="bmc-legal">{{ __('Déjà un compte ?') }} <a href="{{ route('account.roles') }}">{{ __('Connectez-vous pour candidater') }}</a>.</p>
                            @endguest
                        </form>
                    @endif
                </div>
            </div>
        </section>

        <section class="bmc-faq" aria-labelledby="bmc-faq-title">
            <div class="bmc-wrap bmc-faq__inner">
                <div><h2 id="bmc-faq-title" class="bmc-display" data-reveal-title data-reveal-title-duration="1" data-reveal-title-once="true">{{ __('Questions fréquentes') }}</h2></div>
                <div class="bmc-faq__list" data-creator-faq data-blur-reveal data-blur-stagger="0.09" data-blur-duration="0.75" data-blur-y="14" data-blur-pixels="6" data-blur-once="true">
                    <details data-blur-reveal-item>
                        <summary>{{ __('Faut-il beaucoup d’abonné·es ?') }}</summary>
                        <div class="bmc-faq__panel" data-creator-faq-panel>
                            <p>{{ __('Non. Nous regardons avant tout la qualité des contenus et votre lien avec votre communauté.') }}</p>
                        </div>
                    </details>
                    <details data-blur-reveal-item>
                        <summary>{{ __('Comment obtenir mon code ?') }}</summary>
                        <div class="bmc-faq__panel" data-creator-faq-panel>
                            <p>{{ __('Après approbation de votre candidature et confirmation de votre e-mail, vous pourrez choisir votre code unique dans Mon espace influenceur.') }}</p>
                        </div>
                    </details>
                    <details data-blur-reveal-item>
                        <summary>{{ __('La candidature est-elle gratuite ?') }}</summary>
                        <div class="bmc-faq__panel" data-creator-faq-panel>
                            <p>{{ __('Oui. Envoyer votre candidature est gratuit et ne vous engage à aucun achat.') }}</p>
                        </div>
                    </details>
                </div>
            </div>
        </section>
        <section class="bmc-sign" aria-label="BLACK MILK">
            <div class="bmc-wrap"><p class="bmc-display" data-reveal data-reveal-y="12" data-reveal-duration="0.8" data-reveal-once="true">Avec amour<br>par Smirnova<sup>/01</sup></p></div>
        </section>
    </main>
</x-layout>
