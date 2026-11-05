<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Politique de confidentialité + Politique de cookies (pages éditables dans l'admin).
 * Insère les pages seulement si elles n'existent pas déjà : un contenu modifié
 * dans l'admin n'est jamais écrasé.
 */
return new class extends Migration
{
    private const UPDATED = '5 novembre 2026';

    public function up(): void
    {
        $this->insert(
            slug: 'politique-de-confidentialite',
            title: 'Politique de confidentialité',
            metaTitle: 'Politique de confidentialité | BLACK MILK',
            metaDescription: 'Comment BLACK MILK collecte, utilise et protège vos données personnelles (RGPD) : finalités, durées de conservation, destinataires et exercice de vos droits.',
            body: $this->privacyBody(),
            sort: 30,
        );

        $this->insert(
            slug: 'politique-de-cookies',
            title: 'Politique de cookies',
            metaTitle: 'Politique de cookies | BLACK MILK',
            metaDescription: 'Cookies et traceurs utilisés sur blackmilkfrance.com : traceurs strictement nécessaires, traceurs soumis à votre consentement et gestion de vos choix.',
            body: $this->cookiesBody(),
            sort: 31,
        );
    }

    public function down(): void
    {
        DB::table('pages')
            ->whereIn(DB::raw("slug->>'fr'"), ['politique-de-confidentialite', 'politique-de-cookies'])
            ->delete();
    }

    private function insert(string $slug, string $title, string $metaTitle, string $metaDescription, string $body, int $sort): void
    {
        $exists = DB::table('pages')
            ->whereRaw("slug->>'fr' = ?", [$slug])
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return;
        }

        $json = fn (string $value): string => json_encode(['fr' => $value], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        DB::table('pages')->insert([
            'active' => true,
            'sort' => $sort,
            'title' => $json($title),
            'slug' => $json($slug),
            'body' => $json($body),
            'meta_title' => $json($metaTitle),
            'meta_description' => $json($metaDescription),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function privacyBody(): string
    {
        $updated = self::UPDATED;

        return <<<HTML
<p><em>Dernière mise à jour : {$updated}</em></p>

<p>BLACK MILK attache une grande importance à la protection de votre vie privée. La présente politique vous explique, de façon claire et transparente, quelles données personnelles nous collectons lorsque vous utilisez le site <strong>blackmilkfrance.com</strong>, pourquoi nous les utilisons, combien de temps nous les conservons, avec qui nous les partageons et comment exercer vos droits. Elle est établie conformément au Règlement (UE) 2016/679 du 27 avril 2016 (« RGPD ») et à la loi n° 78-17 du 6 janvier 1978 modifiée relative à l’informatique, aux fichiers et aux libertés.</p>

<h2>1. Responsable du traitement</h2>
<p>Le responsable du traitement de vos données est :</p>
<p>
    <strong>BLACK MILK</strong>, EURL au capital de 100 €<br>
    31 avenue du Beau Site, 77400 Lagny-sur-Marne, France<br>
    SIREN 942 874 439 – RCS Meaux<br>
    Représentée par Elena Smirnova, gérante<br>
    E-mail : <a href="mailto:office.blackmilk@gmail.com">office.blackmilk@gmail.com</a> – Téléphone : +33 7 44 83 81 87
</p>
<p>BLACK MILK n’est pas tenue de désigner un délégué à la protection des données (DPO). Toute question relative à vos données peut être adressée aux coordonnées ci-dessus.</p>

<h2>2. Données collectées, finalités et bases légales</h2>
<p>Nous ne collectons que les données nécessaires aux finalités décrites ci-dessous. Les champs obligatoires de nos formulaires sont signalés comme tels ; à défaut de les renseigner, nous ne pourrons pas traiter votre demande ou votre commande.</p>

<div class="table-wrap">
<table>
    <thead>
        <tr>
            <th>Finalité</th>
            <th>Données concernées</th>
            <th>Base légale (RGPD)</th>
            <th>Durée de conservation</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Commande, paiement et livraison</strong> : enregistrement et suivi de votre commande, expédition, service après-vente, exercice du droit de rétractation et garanties légales</td>
            <td>Nom, prénom, adresse e-mail, téléphone, adresse de livraison, pays, contenu et montant de la commande, mode de livraison, point relais choisi, numéro de suivi, statut du paiement et identifiants techniques de transaction. <strong>Nous ne conservons aucun numéro de carte bancaire</strong> : le paiement est traité directement par notre prestataire.</td>
            <td>Exécution du contrat (art. 6.1.b)</td>
            <td>Durée de la relation commerciale, puis archivage de 5 ans (prescription commerciale, art. L110-4 du Code de commerce)</td>
        </tr>
        <tr>
            <td><strong>Obligations comptables et fiscales</strong></td>
            <td>Factures, justificatifs de paiement et données de facturation</td>
            <td>Obligation légale (art. 6.1.c)</td>
            <td>10 ans (art. L123-22 du Code de commerce)</td>
        </tr>
        <tr>
            <td><strong>Compte client</strong> et accès aux formations en ligne</td>
            <td>Nom, adresse e-mail, mot de passe (stocké sous forme chiffrée, jamais en clair), historique de commandes, droits d’accès aux contenus</td>
            <td>Exécution du contrat (art. 6.1.b)</td>
            <td>Durée de vie du compte ; suppression à votre demande ou après 3 ans d’inactivité</td>
        </tr>
        <tr>
            <td><strong>E-mails de service</strong> : confirmation de paiement, information en cas d’échec de paiement, rappel pour finaliser une commande en cours</td>
            <td>Adresse e-mail, numéro et état de la commande</td>
            <td>Exécution du contrat (art. 6.1.b) ; intérêt légitime à mener à bien la vente (art. 6.1.f)</td>
            <td>Aussi longtemps que la commande concernée est conservée</td>
        </tr>
        <tr>
            <td><strong>Candidature au programme créatrices</strong></td>
            <td>Nom, adresse e-mail, profil sur les réseaux sociaux, message libre</td>
            <td>Mesures précontractuelles prises à votre demande (art. 6.1.b)</td>
            <td>3 ans maximum à compter du dernier contact</td>
        </tr>
        <tr>
            <td><strong>Demandes de contact et assistance</strong> (e-mail, messagerie des réseaux sociaux)</td>
            <td>Identité, coordonnées, contenu de votre message</td>
            <td>Intérêt légitime à répondre à vos demandes (art. 6.1.f)</td>
            <td>3 ans maximum à compter du dernier échange</td>
        </tr>
        <tr>
            <td><strong>Sécurité du site, prévention de la fraude et des abus</strong></td>
            <td>Adresse IP, type de navigateur, date et heure des connexions, données de session</td>
            <td>Intérêt légitime à sécuriser le site et les paiements (art. 6.1.f)</td>
            <td>Session : 2 heures maximum ; journaux techniques : 12 mois maximum</td>
        </tr>
        <tr>
            <td><strong>Cookies et traceurs</strong></td>
            <td>Voir notre <a href="/politique-de-cookies">politique de cookies</a></td>
            <td>Consentement (art. 6.1.a), sauf traceurs strictement nécessaires</td>
            <td>13 mois maximum ; votre choix est conservé 6 mois</td>
        </tr>
    </tbody>
</table>
</div>

<p>BLACK MILK n’utilise pas vos données pour de la prospection commerciale sans votre consentement préalable, et ne vend ni ne loue vos données à des tiers. Aucune décision produisant des effets juridiques à votre égard n’est prise de manière exclusivement automatisée.</p>

<h2>3. Origine des données</h2>
<p>Les données traitées sont celles que vous nous communiquez directement (formulaire de commande, création de compte, candidature, messages). Nos prestataires de paiement et de livraison nous transmettent en retour des informations strictement nécessaires au suivi de votre commande (état du paiement, état de la livraison).</p>

<h2>4. Destinataires des données</h2>
<p>Vos données sont destinées aux personnes habilitées de BLACK MILK et, dans la limite de ce qui est nécessaire, à nos prestataires, qui agissent selon nos instructions ou selon leurs propres obligations légales :</p>
<ul>
    <li><strong>Paiement</strong> : Stripe Payments Europe, Ltd. (Irlande), qui sécurise l’encaissement des paiements par carte bancaire et, le cas échéant, PayPal. Vous êtes redirigé vers une page de paiement hébergée par le prestataire ; BLACK MILK ne reçoit ni ne conserve vos données de carte. Ces prestataires traitent aussi certaines données pour leurs propres obligations (lutte contre la fraude, contre le blanchiment) ; voir leurs politiques de confidentialité respectives.</li>
    <li><strong>Livraison</strong> : Sendcloud (Sendcloud B.V., Pays-Bas), plateforme d’expédition, et les transporteurs ou points relais choisis, qui reçoivent vos nom, adresse, téléphone et e-mail afin d’acheminer votre colis et de vous informer de sa livraison.</li>
    <li><strong>Envoi d’e-mails de service</strong> : prestataire technique d’envoi d’e-mails transactionnels.</li>
    <li><strong>Alerte interne des commandes</strong> : la réception d’une commande payée est notifiée à l’équipe BLACK MILK par une messagerie professionnelle (Telegram), qui contient les informations nécessaires à la préparation de la commande.</li>
    <li><strong>Polices d’écriture</strong> : le site charge ses polices depuis les serveurs de Google (Google Fonts) ; votre adresse IP est transmise à Google lors de l’affichage des pages.</li>
    <li><strong>Hébergement et maintenance technique</strong> : l’hébergeur du site, dont l’identité figure dans nos <a href="/mentions-legales">mentions légales</a>.</li>
    <li><strong>Autorités</strong> : toute autorité administrative ou judiciaire habilitée, lorsque la loi nous y oblige.</li>
</ul>

<h2>5. Transferts de données hors de l’Union européenne</h2>
<p>Certains de nos prestataires peuvent traiter des données en dehors de l’Espace économique européen. Dans ce cas, le transfert est encadré par l’un des mécanismes prévus au chapitre V du RGPD : décision d’adéquation de la Commission européenne (notamment le Data Privacy Framework pour les entreprises américaines certifiées), clauses contractuelles types ou autre garantie appropriée. Vous pouvez en obtenir une copie ou plus d’informations en nous écrivant à l’adresse indiquée à l’article 1.</p>

<h2>6. Sécurité</h2>
<p>Nous mettons en œuvre des mesures techniques et organisationnelles adaptées pour protéger vos données contre la perte, l’accès non autorisé, l’altération ou la divulgation : connexion chiffrée (HTTPS), mots de passe chiffrés, accès restreints aux seules personnes habilitées, paiement délégué à un prestataire certifié. En cas de violation de données susceptible d’engendrer un risque élevé pour vos droits et libertés, nous vous en informerons et notifierons la CNIL dans les conditions prévues par la loi.</p>

<h2>7. Vos droits</h2>
<p>Conformément au RGPD et à la loi Informatique et Libertés, vous disposez des droits suivants sur vos données :</p>
<ul>
    <li><strong>droit d’accès</strong> : obtenir la confirmation que vos données sont traitées et en recevoir une copie ;</li>
    <li><strong>droit de rectification</strong> : faire corriger des données inexactes ou incomplètes ;</li>
    <li><strong>droit à l’effacement</strong> : demander la suppression de vos données, sauf lorsque nous devons les conserver pour respecter une obligation légale ou pour constater, exercer ou défendre des droits en justice ;</li>
    <li><strong>droit à la limitation</strong> du traitement ;</li>
    <li><strong>droit d’opposition</strong> aux traitements fondés sur notre intérêt légitime, pour des raisons tenant à votre situation particulière, et à tout moment à la prospection commerciale ;</li>
    <li><strong>droit à la portabilité</strong> : recevoir vos données dans un format structuré et couramment utilisé, pour celles que vous nous avez fournies et qui sont traitées sur la base du contrat ou de votre consentement ;</li>
    <li><strong>droit de retirer votre consentement</strong> à tout moment, sans remettre en cause la licéité du traitement effectué avant ce retrait ;</li>
    <li><strong>droit de définir des directives</strong> relatives au sort de vos données après votre décès.</li>
</ul>
<p>Pour exercer vos droits, écrivez-nous à <a href="mailto:office.blackmilk@gmail.com">office.blackmilk@gmail.com</a> ou par courrier à BLACK MILK, 31 avenue du Beau Site, 77400 Lagny-sur-Marne. En cas de doute raisonnable sur votre identité, nous pourrons vous demander un justificatif ; il ne sera utilisé que pour traiter votre demande. Nous vous répondons dans un délai d’un mois, prolongeable de deux mois en cas de demande complexe ou de demandes multiples (nous vous en informerons).</p>

<h2>8. Réclamation auprès de la CNIL</h2>
<p>Si, après nous avoir contactés, vous estimez que vos droits ne sont pas respectés, vous pouvez introduire une réclamation auprès de la Commission nationale de l’informatique et des libertés (CNIL) : 3 place de Fontenoy, TSA 80715, 75334 Paris Cedex 07 – <a href="https://www.cnil.fr/fr/plaintes" rel="noopener noreferrer" target="_blank">www.cnil.fr/fr/plaintes</a>.</p>

<h2>9. Mineurs</h2>
<p>Le site s’adresse à des professionnelles et à des clientes majeures. Il n’est pas destiné aux enfants de moins de 15 ans et nous ne collectons pas sciemment de données les concernant. Si vous pensez qu’un mineur nous a transmis des données, contactez-nous afin que nous les supprimions.</p>

<h2>10. Cookies et traceurs</h2>
<p>Le site utilise des cookies et traceurs. Leur nature, leur finalité, leur durée et la manière de gérer vos choix sont détaillées dans notre <a href="/politique-de-cookies">politique de cookies</a>.</p>

<h2>11. Modification de la politique</h2>
<p>Nous pouvons mettre à jour cette politique pour tenir compte d’évolutions légales ou de nos services. La date de dernière mise à jour figure en haut de la page ; en cas de modification substantielle, nous vous en informerons de manière appropriée.</p>

<p>Voir aussi : <a href="/mentions-legales">Mentions légales</a> · <a href="/conditions-generales-de-vente">Conditions générales de vente</a>.</p>
HTML;
    }

    private function cookiesBody(): string
    {
        $updated = self::UPDATED;

        return <<<HTML
<p><em>Dernière mise à jour : {$updated}</em></p>

<p>Cette politique explique quels cookies et autres traceurs sont utilisés sur <strong>blackmilkfrance.com</strong>, à quoi ils servent et comment vous pouvez accepter, refuser ou modifier vos choix à tout moment. Elle complète notre <a href="/politique-de-confidentialite">politique de confidentialité</a>.</p>

<h2>1. Qu’est-ce qu’un cookie ou un traceur ?</h2>
<p>Un cookie est un petit fichier enregistré sur votre appareil (ordinateur, tablette, téléphone) lorsque vous consultez un site. Le terme « traceur » désigne plus largement tout mécanisme qui lit ou écrit des informations sur votre appareil, y compris le stockage local du navigateur (<em>localStorage</em>, <em>sessionStorage</em>). Ces dispositifs sont encadrés par l’article 82 de la loi Informatique et Libertés (transposition de la directive ePrivacy) et par les lignes directrices et recommandations de la CNIL du 17 septembre 2020.</p>

<h2>2. Traceurs strictement nécessaires (sans consentement)</h2>
<p>Certains traceurs sont indispensables au fonctionnement du site ou à un service que vous demandez expressément. La réglementation les exempte de consentement ; ils ne peuvent donc pas être désactivés depuis notre bandeau (mais vous pouvez les bloquer dans votre navigateur, au risque de limiter le fonctionnement du site).</p>

<div class="table-wrap">
<table>
    <thead>
        <tr>
            <th>Nom</th>
            <th>Émetteur</th>
            <th>Finalité</th>
            <th>Durée</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>black-milk-session</code></td>
            <td>BLACK MILK</td>
            <td>Maintien de votre session (connexion à votre compte, déroulement de la commande)</td>
            <td>2 heures maximum</td>
        </tr>
        <tr>
            <td><code>XSRF-TOKEN</code></td>
            <td>BLACK MILK</td>
            <td>Sécurité : protection des formulaires contre les requêtes frauduleuses</td>
            <td>2 heures maximum</td>
        </tr>
        <tr>
            <td><code>cc_cookie</code></td>
            <td>BLACK MILK</td>
            <td>Mémorisation de vos choix en matière de cookies</td>
            <td>6 mois</td>
        </tr>
        <tr>
            <td><code>bm_cart</code> (stockage local)</td>
            <td>BLACK MILK</td>
            <td>Conservation du contenu de votre panier</td>
            <td>Jusqu’à la vidange du panier ou de votre navigateur</td>
        </tr>
        <tr>
            <td><code>bm_unpaid_order</code> (stockage local), <code>bm_pending_order</code> et <code>bm_checkout_activation_*</code> (stockage de session)</td>
            <td>BLACK MILK</td>
            <td>Reprise d’une commande en cours de paiement et sécurisation de son suivi</td>
            <td>Jusqu’à la fin du paiement ou de la session du navigateur</td>
        </tr>
    </tbody>
</table>
</div>

<h2>3. Traceurs soumis à votre consentement</h2>
<p>D’autres traceurs ne sont déposés ou lus qu’<strong>après votre accord</strong>. Ils relèvent de la catégorie « cookies marketing » : mesure de l’efficacité de nos campagnes publicitaires et affichage d’offres adaptées à vos centres d’intérêt (par exemple via Meta Pixel ou des plateformes publicitaires). Aucun de ces traceurs n’est activé tant que vous n’avez pas fait votre choix.</p>
<ul>
    <li>Lors de votre première visite, un bandeau vous permet de <strong>tout accepter</strong>, de <strong>tout refuser</strong> ou de <strong>gérer vos préférences</strong> par catégorie ; refuser est aussi simple qu’accepter.</li>
    <li>L’accès au site ne dépend pas de votre acceptation de ces traceurs (aucun « mur de cookies »).</li>
    <li>Votre choix est conservé pendant <strong>6 mois</strong>, puis nous vous le demanderons à nouveau.</li>
    <li>Cette politique est mise à jour chaque fois qu’un nouveau traceur soumis à consentement est ajouté au site.</li>
</ul>

<h2>4. Gérer vos choix</h2>
<p>Vous pouvez modifier ou retirer votre consentement à tout moment, aussi simplement que vous l’avez donné :</p>
<p>
    <button type="button" class="cookie-settings-button" data-cc="show-preferencesModal">Modifier mes choix de cookies</button>
</p>
<p>Vous pouvez aussi paramétrer votre navigateur pour bloquer ou supprimer les cookies :</p>
<ul>
    <li><a href="https://support.google.com/chrome/answer/95647?hl=fr" rel="noopener noreferrer" target="_blank">Google Chrome</a></li>
    <li><a href="https://support.mozilla.org/fr/kb/protection-renforcee-contre-pistage-firefox-ordinateur" rel="noopener noreferrer" target="_blank">Mozilla Firefox</a></li>
    <li><a href="https://support.apple.com/fr-fr/guide/safari/sfri11471/mac" rel="noopener noreferrer" target="_blank">Safari</a></li>
    <li><a href="https://support.microsoft.com/fr-fr/microsoft-edge/supprimer-les-cookies-dans-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" rel="noopener noreferrer" target="_blank">Microsoft Edge</a></li>
</ul>

<h2>5. Paiement et services tiers</h2>
<p>Lors du paiement, vous êtes redirigé vers la page sécurisée de notre prestataire de paiement (Stripe, et le cas échéant PayPal). Ces pages ne sont pas gérées par BLACK MILK : le prestataire peut y utiliser ses propres traceurs, notamment pour prévenir la fraude, conformément à sa propre politique. Par ailleurs, le site charge ses polices d’écriture depuis Google Fonts, ce qui implique la transmission de votre adresse IP à Google (voir la <a href="/politique-de-confidentialite">politique de confidentialité</a>).</p>

<h2>6. Vos droits et contact</h2>
<p>Pour toute question sur les cookies ou pour exercer vos droits sur vos données, écrivez-nous à <a href="mailto:office.blackmilk@gmail.com">office.blackmilk@gmail.com</a> ou à BLACK MILK, 31 avenue du Beau Site, 77400 Lagny-sur-Marne. Vous pouvez également saisir la CNIL : <a href="https://www.cnil.fr/fr/plaintes" rel="noopener noreferrer" target="_blank">www.cnil.fr/fr/plaintes</a>. Pour en savoir plus sur les cookies : <a href="https://www.cnil.fr/fr/cookies-et-autres-traceurs" rel="noopener noreferrer" target="_blank">cnil.fr/fr/cookies-et-autres-traceurs</a>.</p>

<p>Voir aussi : <a href="/mentions-legales">Mentions légales</a> · <a href="/conditions-generales-de-vente">Conditions générales de vente</a>.</p>
HTML;
    }
};
