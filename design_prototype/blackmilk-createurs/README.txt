BLACK MILK — rubrique « Créatrices » (UGC & influence)
Paquet prêt à intégrer · octobre 2026
========================================================

CONTENU
-------
bloc-accueil.html            Bloc à insérer sur la page d’accueil (après « Produits phares »).
page-createurs.html          Contenu de la page /createurs (entre l’en-tête et le pied de page du site).
assets/createurs.css         Styles. Tout est préfixé .bmc : aucun impact sur le reste du site.
assets/createurs.js          Formulaire en 4 étapes (validation, envoi). Aucune dépendance.
images/createurs-modele-cire.jpg   Photo (1442 × 1091).
apercu-page-createurs.html   Aperçu à ouvrir dans un navigateur (double-clic).
apercu-bloc-accueil.html     Aperçu du bloc d’accueil.
docs/                        Cahier des charges (PDF + Word), modèle de contrat, version russe.

INTÉGRATION — 4 ÉTAPES
----------------------
1. Polices : dans le <head> du site (une seule fois)
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&family=Pinyon+Script&family=Playfair+Display:ital,wght@0,400;0,500;1,400&display=swap">

2. Fichiers : copier assets/ et images/ sur le serveur, puis
   <link rel="stylesheet" href="/chemin/assets/createurs.css">          (dans le <head>)
   <script src="/chemin/assets/createurs.js" defer></script>              (avant </body>, page /createurs uniquement)
   Adapter les chemins src="images/…" si les images sont ailleurs.

3. HTML :
   - coller page-createurs.html dans une nouvelle page, URL : /createurs
   - coller bloc-accueil.html sur la page d’accueil
   - ajouter « CRÉATRICES » au menu (après FORMATIONS) et « Programme créatrices » dans le pied de page, vers /createurs

4. Envoi du formulaire : dans page-createurs.html, sur <form id="bmc-form">, renseigner
   data-endpoint="https://…"  (URL qui reçoit un POST multipart/form-data et répond 2xx).
   Tant que c’est vide, le formulaire fonctionne en MODE DÉMO : il affiche « Merci » sans rien envoyer.
   Possibilités :
   - Shopify : app de formulaires (ex. Shopify Forms, Hulk, Globo) → reprendre les noms de champs ci-dessous
   - WordPress : Gravity Forms / WPForms / Contact Form 7 (webhook) ou endpoint REST
   - Sans back-end : Tally, Formspree, ou webhook n8n → Notion / Airtable + e-mail
   Pensez à l’e-mail de confirmation à la candidate et à l’alerte à office.blackmilk@gmail.com (voir cahier des charges, section « Statuts »).

CHAMPS ENVOYÉS (name)
---------------------
prenom, nom, email, telephone, ville, majeure (Oui/Non)
reseau, profil, abonnes, autres_profils, statistiques[] (fichiers, 3 max, 10 Mo max chacun)
profil_type[], formats[], tests_longs (oui), statut
motivation (30–600 caractères), consentement_rgpd (oui, obligatoire), newsletter (oui, facultatif)
website = piège anti-robot : doit rester vide, rejeter côté serveur si rempli.

À VÉRIFIER AVANT MISE EN LIGNE
------------------------------
- Logo : la page n’utilise pas de logo image ; le sceau « BM » est en texte. Rien à remplacer.
- Lien /politique-de-confidentialite : adapter à l’URL réelle et y ajouter le paragraphe « Programme créatrices ».
- Ajouter une protection anti-spam côté serveur (hCaptcha ou Cloudflare Turnstile), sans traceur Google.
- Mineures : « Non » à « J’ai 18 ans ou plus » bloque l’envoi ; ne rien enregistrer dans ce cas.
- Données : hébergement UE, durées de conservation (12 mois si refus, 24 mois liste d’attente, 5 ans après contrat).
- Photo : l’accord écrit de la modèle pour l’usage sur le site doit être disponible.
- Design : la référence est celle des fichiers d’aperçu (rouge cerise, crème, bleu poudré, rayures, damier, étoiles).
  La section « Дизайн » du cahier des charges décrit une version précédente : suivre les aperçus.
- Testé : Chrome desktop 1400 px et mobile 390 px, sans défilement horizontal ; navigation clavier.

Contact : office.blackmilk@gmail.com
