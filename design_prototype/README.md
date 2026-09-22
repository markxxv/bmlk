# BLACK MILK — Site web (refonte 2026)

Site vitrine et catalogue BLACK MILK France : 8 pages HTML, multilingue FR / EN / RO, entièrement statique.
Aucun framework, aucune compilation, aucune dépendance à installer : le dossier s'ouvre tel quel.

## Ouvrir le site

1. Décompresser l'archive.
2. Ouvrir `index.html` dans un navigateur (Chrome, Safari, Firefox, Edge).

Les 8 pages sont liées entre elles par le menu. Le sélecteur de langue (FR / EN / RO) se souvient du choix entre les pages.

## Mettre en ligne

Déposer **l'intégralité du dossier** (fichiers et sous-dossiers) à la racine de l'hébergement. `index.html` est la page d'accueil. Aucune configuration serveur nécessaire.

Pour tester avec un serveur local (optionnel) :

    python3 -m http.server 8080
    # puis ouvrir http://localhost:8080

## Structure

    index.html            Accueil
    boutique.html         Gels Corex, Bases & Tops, Poly Gel
    soins.html            Soins & essentiels
    outils.html           Outils, fraises, ciseaux, accessoires
    formations.html       Online Education, LIVE Education, fiche cours
    evenements.html       Rendez-vous 2027, calendrier filtrable
    representants.html    Représentants par pays, recrutement
    centre-client.html    Support, suivi, réclamations, avis
    legal.html            Mentions légales, CGV, confidentialité, cookies
    catalogue.html        Catalogue produits (consultation)
    catalogue-visuel.html Catalogue visuel (photos + noms)
    audit.html            Rapport d'audit (interne)

    support.js            Moteur de rendu des pages (ne pas modifier)
    image-slot.js         Emplacements photo glisser-déposer
    i18n.js               Système de traduction
    swatches-data.js      Données des teintes
    bm-consent.js         Bandeau cookies (RGPD)
    bm-mascot.js          Vachette animée (coin d'écran)
    cow-frame.html        Vachette du cercle d'accueil
    cow-relay.js          Suivi du curseur pour la vachette

    brand/    logo, vachette, portraits, soins, coffrets cire
    sw/       nuancier Gel Corex (traits)
    pots/     Gel Corex (pots)
    basetop/  Bases & Tops (traits)
    basetop2/ Bases & Tops (gouttes)
    bottles/  Bases & Tops (flacons)
    tools/    outils, fraises, ciseaux, objectifs, limes
    boxes/    coffrets
    cours/    photos des formations
    guides/   guides d'application

## Modifier le contenu

Les textes, prix et listes de produits sont dans chaque page, dans le bloc `<script type="text/x-dc" data-dc-script>`, sous les clés `FR`, `EN`, `RO`. Les photos s'ajoutent dans le dossier correspondant et se référencent par leur chemin relatif.

## À brancher lors de la mise en ligne

- **Paiement / panier** : les boutons « Ajouter au panier » sont visuels — à connecter à la plateforme e-commerce choisie.
- **Formulaires** (réclamations, candidatures, newsletter, avis) : ouvrent un e-mail pré-rempli vers office.blackmilk@gmail.com ; à remplacer par un envoi serveur.
- **Événements** : boutons d'inscription à relier au système de réservation (SimplyBook.me recommandé dans le brief).
- **Avis et notes** : stockés localement dans le navigateur du visiteur ; prévoir une base côté serveur pour des avis partagés.
- **Mentions légales** : compléter les champs marqués « à compléter » dans `legal.html`.

## Navigateurs

Chrome, Safari, Firefox, Edge — versions récentes. Responsive ordinateur / tablette / téléphone.

© BLACK MILK France
