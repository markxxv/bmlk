import * as CookieConsent from 'vanilla-cookieconsent';
import 'vanilla-cookieconsent/dist/cookieconsent.css';
import '../css/cookieconsent.css';

const lang = (document.documentElement.lang || 'fr').toLowerCase().slice(0, 2);
const ccLang = lang === 'fr' ? 'fr' : 'en';

const links = {
    privacy: '/politique-de-confidentialite',
    cookies: '/politique-de-cookies',
};

export function initCookieConsent() {
    CookieConsent.run({
        mode: 'opt-in',
        revision: 1,

        // Choice kept 6 months (CNIL recommendation), then asked again.
        cookie: {
            name: 'cc_cookie',
            expiresAfterDays: 182,
            sameSite: 'Lax',
        },

        guiOptions: {
            consentModal: {
                layout: 'box inline',
                position: 'bottom right',
                equalWeightButtons: true,
                flipButtons: true,
            },
            preferencesModal: {
                layout: 'box',
                position: 'right',
                equalWeightButtons: true,
                flipButtons: false,
            },
        },

        categories: {
            necessary: {
                enabled: true,
                readOnly: true,
            },
            // analytics: {},
            marketing: {},
        },

        language: {
            default: ccLang,
            autoDetect: 'document',
            translations: {
                en: {
                    consentModal: {
                        title: 'Manage cookie consent',
                        description: `We use cookies to make the site work smoothly. You can read more in our <a class="cc__link" href="${links.cookies}">cookies policy</a>.`,
                        acceptAllBtn: 'Accept all',
                        acceptNecessaryBtn: 'Reject all',
                        showPreferencesBtn: 'Manage preferences',
                        footer: `<a href="${links.privacy}">Privacy policy</a>\n<a href="${links.cookies}">Cookies policy</a>`,
                    },
                    preferencesModal: {
                        title: 'Consent preferences center',
                        acceptAllBtn: 'Accept all',
                        acceptNecessaryBtn: 'Reject all',
                        savePreferencesBtn: 'Save preferences',
                        closeIconLabel: 'Close',
                        serviceCounterLabel: 'Service|Services',
                        sections: [
                            {
                                title: 'Cookie usage',
                                description: 'To provide the best experience, we use technologies like cookies to store and/or access device information. Consenting to these technologies allows us to process data such as browsing behavior. Not consenting, or withdrawing consent, may affect certain features.',
                            },
                            {
                                title: 'Strictly necessary cookies <span class="pm__badge">Always enabled</span>',
                                description: 'These cookies let the website work properly: your cart, your session and the checkout depend on them. They cannot be turned off because the site would not function without them.',
                                linkedCategory: 'necessary',
                            },
                            // {
                            //     title: 'Analytics cookies',
                            //     description: 'Used to understand how visitors interact with the website.',
                            //     linkedCategory: 'analytics',
                            // },
                            {
                                title: 'Marketing cookies',
                                description: 'These cookies help us show relevant offers and measure the effectiveness of our advertising (including services such as Meta Pixel and advertising platforms). They are only used after your consent.',
                                linkedCategory: 'marketing',
                            },
                            {
                                title: 'More information',
                                description: `For any question about our cookie policy and your choices, read our <a class="cc__link" href="${links.cookies}">cookies policy</a>.`,
                            },
                        ],
                    },
                },
                fr: {
                    consentModal: {
                        title: 'Gérer le consentement aux cookies',
                        description: `Nous utilisons des cookies pour garantir le bon fonctionnement du site. Vous pouvez en savoir plus dans notre <a class="cc__link" href="${links.cookies}">politique de cookies</a>.`,
                        acceptAllBtn: 'Tout accepter',
                        acceptNecessaryBtn: 'Tout refuser',
                        showPreferencesBtn: 'Gérer les préférences',
                        footer: `<a href="${links.privacy}">Politique de confidentialité</a>\n<a href="${links.cookies}">Politique de cookies</a>`,
                    },
                    preferencesModal: {
                        title: 'Centre de préférences de consentement',
                        acceptAllBtn: 'Tout accepter',
                        acceptNecessaryBtn: 'Tout refuser',
                        savePreferencesBtn: 'Enregistrer les préférences',
                        closeIconLabel: 'Fermer la fenêtre',
                        serviceCounterLabel: 'Service|Services',
                        sections: [
                            {
                                title: 'Utilisation des cookies',
                                description: 'Pour vous offrir la meilleure expérience, nous utilisons des technologies comme les cookies pour stocker et/ou accéder aux informations de votre appareil. Consentir à ces technologies nous permet de traiter des données telles que votre comportement de navigation. Le refus ou le retrait du consentement peut affecter certaines fonctionnalités du site.',
                            },
                            {
                                title: 'Cookies strictement nécessaires <span class="pm__badge">Toujours activés</span>',
                                description: 'Ces cookies essentiels permettent au site de fonctionner correctement : votre session, votre panier et le paiement en dépendent. Ils ne peuvent pas être désactivés, sans quoi le site ne fonctionnerait pas.',
                                linkedCategory: 'necessary',
                            },
                            // {
                            //     title: 'Cookies d’analyse',
                            //     description: 'Ces cookies nous permettent de comprendre comment les visiteurs utilisent le site afin d’améliorer ses performances et l’expérience utilisateur.',
                            //     linkedCategory: 'analytics',
                            // },
                            {
                                title: 'Cookies marketing',
                                description: 'Ces cookies nous permettent de proposer des offres pertinentes et de mesurer l’efficacité de nos campagnes publicitaires (y compris via des services tels que Meta Pixel et des plateformes publicitaires). Ils ne sont utilisés qu’avec votre consentement.',
                                linkedCategory: 'marketing',
                            },
                            {
                                title: 'Plus d’informations',
                                description: `Pour toute question concernant notre politique en matière de cookies et vos choix, veuillez consulter la <a class="cc__link" href="${links.cookies}">politique de cookies</a>.`,
                            },
                        ],
                    },
                },
            },
        },
    });
}
