# BLACK MILK France — Technical Handoff

**Date:** 4 September 2026
**Status of this document:** honest inventory of what exists. **This project is a front-end design deliverable, not a running e-commerce application.**

---

## 0. Read this first — scope correction

A full-stack launch was requested (discovery, environment startup, customer journey, payments, delivery, admin, tests, CI). That work cannot be performed here, and no part of it should be reported as done.

**Phase 1 discovery result: there is no application to launch.** The repository contains no backend, no database, no API layer, no payment integration, no delivery integration, no authentication, no test suite, no build system and no deployment configuration. There is nothing to install, migrate, seed, start or test.

Every item in Phases 2–7 of the brief is therefore **NOT VERIFIED — component does not exist**. The rest of this document states precisely what *does* exist, and what must be built.

---

## 1. What actually exists

### 1.1 Nature of the deliverable

Static, self-contained HTML pages. Each page is a single file: markup, inline styles and a data/logic block. Product catalogue, prices, translations and copy are **hard-coded in JavaScript arrays inside each page file**. There is no data store.

Pages open directly in a browser from the filesystem. No server, no build step, no package manager.

### 1.2 Site pages — production-named set (`livraison/`)

| File | Content |
|---|---|
| `index.html` | Home: hero, key figures, gift sets, categories, bestsellers, Elena block, newsletter |
| `boutique.html` | Gel Corex colour chart, Bases & Tops, Poly Gel |
| `soins.html` | Care products and essentials |
| `outils.html` | Cutting tools, drill bits, brushes, scissors, accessories |
| `formations.html` | Online Education (3 courses) + Live Education (3 courses) |
| `evenements.html` | Workshops, masterclasses, international tour, trade shows, event table |
| `representants.html` | Country representatives + ambassador programme |
| `centre-client.html` | Customer centre, technical support, order-tracking form, reviews |
| `legal.html` | Legal notice, T&Cs, privacy policy, cookies, withdrawal |
| `audit.html` | Audit report (structure, payment, delivery, compliance, stock/accounting) |

Working copies live at the project root with `.dc.html` extensions. `Black Milk - Multilingue.dc.html` is the same content as a single long page, kept as a backup.

### 1.3 Shared assets

| File | Purpose |
|---|---|
| `support.js` | Rendering runtime for the page format. **Required** — pages are blank without it |
| `image-slot.js` | Drag-and-drop image placeholder component (browser-local persistence) |
| `i18n.js` | Translation helper |
| `swatches-data.js` | Embedded gel colour-chart images |
| `bm-mascot.js` | Corner mascot (cursor-tracking cow), self-contained |
| `bm-consent.js` | Cookie consent banner (front-end only — see §6) |
| `cow-frame.html`, `cow-relay.js` | Earlier mascot engine, still referenced by some pages |

### 1.4 Image assets — 366 files, all present

`sw/` 33 gel swatches · `pots/` 55 gel pots · `basetop/` 54 + `basetop2/` 54 strokes and drops · `bottles/` 58 bottles · `tools/` 76 · `boxes/` 4 · `brand/` 50 (logo, mascot, Elena, care products)

### 1.5 Interactive behaviour that does work

Language switch FR/EN/RO · expandable product sections · lightbox with 2–3 photos per shade · event filters · star rating and reviews (**stored in the visitor's own browser only — not shared, not persisted server-side**) · complaint form with attachments (**opens a pre-filled e-mail; nothing is transmitted to a server**) · order-tracking form (**redirects to the carrier's own tracking page**) · cookie banner (**front-end only**).

---

## 2. What does not exist — build list

| Component | Status |
|---|---|
| Backend / API | **Absent** |
| Database, ORM, migrations, seeds | **Absent** |
| Authentication, roles, sessions | **Absent** |
| Cart | **Absent** — "Add to cart" buttons are decorative |
| Checkout / tunnel | **Absent** |
| Payment provider, webhooks | **Absent** |
| VAT engine, OSS, discount codes | **Absent** — prices are static strings |
| Stock / inventory, batch tracking | **Absent** |
| Delivery rates, zones, labels, tracking | **Absent** |
| Transactional e-mail | **Absent** |
| Invoicing (incl. Factur-X) | **Absent** |
| Customer account | **Absent** |
| Admin dashboard | **Absent** |
| File/image upload storage | **Absent** — images are committed files |
| Analytics, real consent gating | **Absent** |
| Background jobs, cron, queues | **Absent** |
| Tests, CI/CD, Docker | **Absent** |
| Hosting / deployment config | **Absent** |

---

## 3. Installation and startup — current reality

```bash
# No dependencies, no build, no server required.
# Open the delivered folder and load the home page:
#   livraison/index.html
```

To serve over HTTP locally (needed if a future build adds `fetch`-based assets):

```bash
cd livraison
python3 -m http.server 8080
# → http://localhost:8080/index.html
```

**Commands that do not exist and must not be documented as if they did:** `npm install`, `npm run dev`, `npm run build`, `npm test`, migrations, `docker compose up`.

There is no `.env` file and no `.env.example`, because no code reads environment variables. Creating a placeholder `.env.example` now would be misleading — it belongs to the build phase, alongside the table in §5.

---

## 4. Recommended target architecture

Two viable routes. Given >250 SKUs, courses, professional accounts and a small team, route A is the rational choice.

**Route A — Shopify (recommended).** Cart, checkout, VAT, OSS, SCA/3-D Secure 2, stock, invoices, transactional e-mail and admin are provided. The existing design is ported as a theme. Payments via Shopify Payments + Shop Pay covers card (CB/Visa/Mastercard/Amex), Apple Pay, Google Pay and European local methods in one integration. Alma app for 2–4 instalments. Sendcloud or Boxtal app for multi-carrier shipping.

**Route B — WooCommerce on the existing WordPress.** More design freedom, but tunnel compliance, VAT, invoicing and security rest on the developer. Stripe or Mollie as the payment core.

Either way: **the pages in this repository are the design source of truth**, not a runtime. Content must be migrated out of the hard-coded arrays into the platform's product records.

---

## 5. Environment variables required by the future build

Names and purposes only — no values, and none of these are currently read by any code.

| Variable | Purpose | Required | Sandbox / Production |
|---|---|---|---|
| `DATABASE_URL` | Database connection | Yes | Separate per environment |
| `APP_URL` | Public base URL, used in e-mails and webhooks | Yes | Both |
| `SESSION_SECRET` | Session/cookie signing | Yes | Distinct per environment |
| `STRIPE_SECRET_KEY` / `STRIPE_PUBLISHABLE_KEY` | Card, Apple Pay, Google Pay | Yes (route B) | `sk_test_` / `sk_live_` |
| `STRIPE_WEBHOOK_SECRET` | Webhook signature verification | Yes | Per endpoint |
| `PAYPAL_CLIENT_ID` / `PAYPAL_SECRET` | PayPal | Recommended | Sandbox / Live |
| `ALMA_API_KEY` | Instalment payments | Recommended | Test / Live |
| `SENDCLOUD_PUBLIC_KEY` / `SENDCLOUD_SECRET_KEY` | Multi-carrier shipping, labels, tracking | Yes | Test / Live |
| `COLISSIMO_CONTRACT` / `COLISSIMO_PASSWORD` | La Poste, if integrated directly | Optional | Both |
| `UPS_CLIENT_ID` / `UPS_CLIENT_SECRET` | UPS | Optional | Test / Live |
| `DHL_API_KEY` | DHL Express | Optional | Test / Live |
| `SMTP_HOST` / `SMTP_USER` / `SMTP_PASSWORD` | Transactional e-mail | Yes | Both |
| `ACCOUNTANT_REPORT_EMAIL` | Recipient of the 30-day report | Yes | Production only |
| `EINVOICE_PLATFORM_ID` / `EINVOICE_API_KEY` | Approved e-invoicing platform (Factur-X) | Yes by 09/2027 | Both |
| `VIES_ENABLED` | Intra-EU VAT number validation | Recommended | Both |
| `S3_BUCKET` / `S3_ACCESS_KEY` / `S3_SECRET_KEY` | Product image storage | Yes | Both |

**Where to obtain them:** payment keys from each provider's dashboard after account approval (KYC required, allow several days); Sendcloud keys from its settings panel; carrier credentials from each carrier's business account; the e-invoicing platform from the DGFiP-registered list — ask the accountant which one they already use.

---

## 6. Compliance items that affect the code

Full detail in `audit.html`. The ones a developer must implement rather than a lawyer:

1. **Checkout must contain** an itemised summary before payment, a separate un-pre-ticked T&Cs checkbox, a button reading "Commander et payer", and an e-mail confirmation on a durable medium (art. L.221-14, L.221-13).
2. **Cookie banner is currently front-end only.** No script may load before consent. Refusing must be as easy as accepting (CNIL guidelines). Instagram embeds and analytics must be gated.
3. **Prices must display inclusive of tax** for consumers, exclusive of tax on verified professional accounts. A struck-through price requires a documented 30-day lowest-price reference (Omnibus).
4. **You cannot disclaim liability for uninsured parcels** toward a consumer — such a clause is irrebuttably unfair and void (art. R.212-1, 6°). Risk transfers only on physical delivery (art. L.216-4). Insurance protects the merchant, not the customer.
5. **Reviews** need a moderation notice, publication and experience dates, and a verified-purchase indicator (art. L.111-7-2).
6. **Accessibility** is mandatory for e-commerce since 28 June 2025 unless exempt as a micro-enterprise. Current pages use clickable `div`s for accordions and language switches with no ARIA or keyboard access — replace with `button` + `aria-expanded`, add `alt` on product images.
7. **Invoices** must carry every mention of art. 289-II CGI, with strictly sequential numbering and 10-year archiving.
8. **E-invoicing:** since 1 September 2026 every French VAT-registered business must be able to *receive* electronic invoices via an approved platform. Issuing becomes mandatory for SMEs on 1 September 2027 (Factur-X, UBL or CII). Require Factur-X output from the start.
9. **Never publish** PIF, safety assessment reports, CPNP notifications, supplier certificates or the director's home address. These are held for inspection by the authorities only. `legal.html` has already been stripped accordingly.

---

## 7. Order status lifecycle — to implement

```
created → payment_pending → paid → preparing → shipped → delivered
                ↓                      ↓
            failed / cancelled     returned → refunded
```

Stock rules: reserve on order creation, decrement on `paid`, release on `failed`/`cancelled`, restore on `returned`. Batch (lot) number recorded on each order line — required for cosmetic traceability and recall.

---

## 8. Tests executed

**None.** There is no test suite, no build, no server and no database in this repository, so nothing could be run. Any statement that catalogue, cart, checkout, payment, delivery, e-mail, invoices, customer account or admin were tested would be false.

| Component | Status |
|---|---|
| Catalogue | Renders as a static design. Not a queryable catalogue |
| Cart | **NOT VERIFIED — does not exist** |
| Checkout | **NOT VERIFIED — does not exist** |
| Payment | **NOT VERIFIED — does not exist** |
| Delivery | **NOT VERIFIED — does not exist** |
| E-mail | **NOT VERIFIED — does not exist** |
| Invoices | **NOT VERIFIED — does not exist** |
| Customer account | **NOT VERIFIED — does not exist** |
| Admin | **NOT VERIFIED — does not exist** |

What *was* checked, in a browser preview: pages load without console errors, language switching works, expandable sections and lightboxes open, event filters respond, all 366 images resolve, and no unresolved template placeholders remain.

**Confirmation:** no real payment, no shipping label, no production customer notification and no production data change was created — none of those systems is connected.

---

## 9. Blockers

| # | Blocker | Corrective action |
|---|---|---|
| 1 | No e-commerce platform chosen | Decide Shopify vs WooCommerce. Nothing else can start |
| 2 | Catalogue data is hard-coded in HTML | Export to CSV, import as platform products |
| 3 | No merchant account | Open Stripe/Shopify Payments and PayPal accounts — KYC takes days |
| 4 | No carrier accounts | Open La Poste, Mondial Relay, UPS, DHL accounts; then Sendcloud/Boxtal |
| 5 | INCI lists, net contents, PAO, batch numbers missing | Obtain from the manufacturer. **Legally blocking for sale** |
| 6 | Responsible person and CPNP notifications unconfirmed | Confirm before any sale. **Legally blocking** |
| 7 | Consumer mediator not appointed | Join an approved scheme (paid, annual). **Legally blocking** |
| 8 | Not registered for VAT OSS | Register before selling to other EU countries |
| 9 | E-invoicing reception not confirmed | Check with the accountant — already mandatory |
| 10 | No hosting | Choose hosting; the static folder can go live immediately as a showcase |
| 11 | ES/PT translations are copies of English | Human translation required |

---

## 10. Troubleshooting

**Pages appear blank.** `support.js` failed to load. It must sit in the same folder as the pages. In a browser console, 401/404 on `support.js` confirms it.

**Images missing.** The folders `sw/ pots/ basetop/ basetop2/ bottles/ tools/ boxes/ brand/` must be deployed alongside the HTML, with the same relative paths.

**`{{ something }}` visible on screen.** The runtime did not boot — same cause as a blank page.

**Old images still showing after replacement.** Browser cache. Image URLs carry a `?v=N` suffix; increment it.

**Reviews or ratings disappeared.** They are stored in the visitor's own browser (`localStorage`), never on a server. This is expected until a backend exists.

**Mascot not visible.** `bm-mascot.js` must be present; it is self-contained and needs no network access.

---

## 11. Files changed in this session

- `Audit.dc.html` and `livraison/audit.html` — added the "Stock & comptabilité" tab (6 items), added the delivery items (carriers, insurance ≥150 €, La Poste wording, dispatch times, free shipping ≥100 €, withdrawal form), added the confidential-information item, removed unsourced statistics and attributed the remaining figures, added a "Méthode & sources" tab, corrected the summary counters.
- `Legal.dc.html` and `livraison/legal.html` — removed responsible-person name and address, CPNP notification number, and references to the PIF and safety assessment report.
- `TECHNICAL_HANDOFF.md` — this file (new).
