# Laravel catalog schema

## Interface language policy

The Filament admin panel (`/admin`) and Warehouse staff PWA (`/warehouse`) are **English-only**. This includes all labels, buttons, validation and error messages, order status text, confirmations, accessibility labels and future interface changes.

The public store, customer account, checkout and customer communications remain French. Keep `APP_LOCALE=fr` and `APP_FALLBACK_LOCALE=fr`; do not switch the global locale.

Filament enforces English in `app/Providers/Filament/AdminPanelProvider.php` using `->bootUsing(fn () => app()->setLocale('en'))`. Warehouse enforces English only for `/warehouse` requests inside `app/Http/Middleware/WarehouseSession.php`. Shared customer status methods and stored customer/order data are not translated globally. All future admin and warehouse UI must follow these rules.

Target stack:

- Laravel
- PostgreSQL
- Filament v5
- Spatie Laravel Media Library v11
- Spatie Laravel Translatable

## Models

- `Category`
- `Product`

Both models use classic Laravel auto-incrementing numeric IDs.

Images are stored only through Spatie Media Library in the `images` collection.

## Translations

Translatable fields use `spatie/laravel-translatable` and PostgreSQL `jsonb`.

Locale keys:

```json
{
  "fr": "...",
  "en": "...",
  "ro": "..."
}
```

`Product::$translatable`:

- `name`
- `slug`
- `tag`
- `description`
- `details`
- `contents`

`Category::$translatable`:

- `name`
- `description`
- `use`
- `price_label`

Product slugs are globally unique per locale. PostgreSQL expression indexes enforce uniqueness for `fr`, `en` and `ro`.

## Media

`Product` implements Spatie `HasMedia`.

Collection:

```php
images
```

Conversion:

```php
thumb: 400x400 WebP, quality 82 (Filament previews, gallery thumbnails and cart)
card: maximum 800x1067 WebP, quality 80 (home, catalog and recommendations)
detail: maximum 1200x1200 WebP, quality 85 (1x product gallery and OG images)
detail_retina: maximum 2400x2400 WebP, quality 85 (2x product gallery)
```

Storefront views use generated variants only. If a new conversion is not yet
available, they temporarily use an existing optimized thumbnail, never a raw
original. New images generate all four conversions synchronously. Product
gallery images use `srcset` resolution descriptors to select `detail` on
standard-density displays and `detail_retina` on high-density displays.
When the Retina variant is not yet generated, the existing `detail` remains
usable without a broken URL.

**After deploying the conversion definitions, regenerate existing product media**:

```bash
php artisan media-library:regenerate 'App\Models\Product' --only=thumb --only=card --only=detail --only=detail_retina
```

If older files were accidentally saved on the private `local` disk, first run
`php artisan media:repair-product-disks` as described below. The regeneration
command writes the conversions to each media record's configured conversion disk.

Product images are always stored on Laravel's `public` disk (`storage/app/public`),
regardless of `FILESYSTEM_DISK`. Both the Filament upload field and the Product
media collection explicitly select this disk.

If images were previously uploaded with `FILESYSTEM_DISK=local`, repair them after
deploying:

```bash
php artisan media:repair-product-disks --dry-run
php artisan media:repair-product-disks
```

The command copies existing product originals and conversions from the private
`local` disk to `public`, verifies copied sizes, then updates the media records.
It retains the private copies as a rollback backup.

For Filament v5:

```php
SpatieMediaLibraryFileUpload::make('images')
    ->collection('images')
    ->disk('public')
    ->multiple()
    ->reorderable()
    ->conversion('thumb');
```

## Catalog import

Command:

```bash
php artisan catalog:import
```

The command imports:

- categories;
- parent category relations;
- products;
- FR / EN / RO translations;
- prices and price ranges;
- options, sizes, measurements, claims and set contents;
- all product images through Spatie Media Library.

The command shows separate progress bars for categories and products.

### Where to put products.json

Copy the final catalog JSON to:

```text
storage/app/import/catalog/products.json
```

### Where to put images

Create:

```text
storage/app/import/catalog/images/
```

Inside it, keep exactly the same relative folders used by `products.json`:

```text
storage/app/import/catalog/
├── products.json
└── images/
    ├── sw/
    ├── pots/
    ├── basetop/
    ├── basetop2/
    ├── bottles/
    ├── brand/
    ├── tools/
    └── boxes/
```

Example:

If JSON contains:

```json
{
  "relative_path": "pots/sweet-candy.jpg"
}
```

the source file must be located at:

```text
storage/app/import/catalog/images/pots/sweet-candy.jpg
```

The importer copies source files into the configured Spatie Media Library disk. The original import files remain untouched.

Before importing media for an existing product, its `images` collection is cleared and rebuilt from JSON, so repeated imports do not create duplicate media rows.

Missing image files do not stop the catalog import. Their paths are shown in the final command summary.

### Custom paths

Defaults can be overridden:

```bash
php artisan catalog:import --json=/absolute/path/products.json --images=/absolute/path/images
```

Relative custom paths are resolved from the Laravel project root:

```bash
php artisan catalog:import --json=storage/import/products.json --images=storage/import/images
```

### Re-import behavior

The import is repeatable:

- categories are matched by their French translated name;
- products are matched by globally unique `slug.fr`;
- existing rows are updated;
- soft-deleted matching rows are restored;
- product media is rebuilt from the source image list.

The string IDs from the extraction JSON such as `gel_corex` are used only inside the importer to map products to the newly created numeric category IDs. They are not stored as database primary keys.

## Files

Copy these files into the corresponding paths of the Laravel project:

```text
app/Models/Category.php
app/Models/Product.php
app/Console/Commands/ImportCatalog.php
database/migrations/2026_09_18_000001_create_categories_table.php
database/migrations/2026_09_18_000002_create_products_table.php
database/migrations/2026_09_18_000003_create_media_table.php
```

The 10 course records under `service_offerings` are intentionally outside the physical product catalog schema.


## Filament v5 layout convention

For this project, Resource forms always use a full-width root schema grid:

```php
Grid::make([
    'default' => 1,
    'xl' => 3,
    '2xl' => 3,
])
    ->schema([
        // Main content
        Grid::make(1)
            ->schema([
                // Sections...
            ])
            ->columnSpan([
                'default' => 1,
                'xl' => 2,
                '2xl' => 2,
            ]),

        // Sidebar
        Grid::make(1)
            ->schema([
                // Settings sections...
            ])
            ->columnSpan([
                'default' => 1,
                'xl' => 1,
                '2xl' => 1,
            ]),
    ])
    ->columnSpanFull();
```

Important: the root `Grid` must always end with `->columnSpanFull()`. Without it, Filament v5 can constrain the entire 2/3 + 1/3 layout into a narrow schema column.


## Payment emails — cron only

Payment emails are sent **synchronously by Laravel Scheduler**. No queues, workers, Supervisor or external scheduler are required.

- **Paid:** one confirmation after Stripe confirms the payment (within the next minute).
- **Failed:** one message after a failed/expired Stripe checkout, or when the initial payment session cannot be created.
- **Reminder:** one follow-up on the **next calendar day after 10:00, Europe/Paris**, if the order remains unpaid. No subsequent reminders.
- Failed-payment emails include a private link to pay the **same order**, without rebuilding the cart.
- Orders without an email address are skipped. Historical paid/failed orders are excluded from initial mailouts.

### 1. Configure SMTP

In the production `.env`, configure a real SMTP provider with verified sender credentials:

```dotenv
APP_URL=https://blackmilk.dak.ink
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS=your-verified-sender@example.com
MAIL_FROM_NAME="BLACK MILK"
```

`MAIL_MAILER=log` is for development only; it does not deliver real email.

### 2. Deploy

```bash
git pull origin main
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
```

### 3. Install one cron entry

Edit the production user's crontab with `crontab -e` and add:

```cron
* * * * * cd /absolute/path/to/bmlk && php artisan schedule:run >> storage/logs/scheduler.log 2>&1
```

Replace `/absolute/path/to/bmlk` with the actual Laravel directory. The command runs once a minute; the reminder cutoff is checked in **Europe/Paris** time (including daylight saving). No queue worker is needed.

### 4. Verify

```bash
php artisan schedule:list
php artisan orders:payment-emails
```

The second command manually sends currently pending emails; only run it after SMTP is correctly configured. Email timestamps are stored on `orders` to prevent repeated mail. SMTP failures are written to `storage/logs/laravel.log` and retried on the next cron run.


## Customer accounts and partner roles

- Checkout requires an email. Stripe confirmation creates a passwordless account when the email is new.
- The original checkout browser is signed into the new account and redirected to `/account` after successful payment. Other browsers or forwarded payment links cannot sign in with an order URL alone.
- Customers set or generate a password in their account; browsers can save it. Email verification and forgotten-password links use standard Laravel mail notifications.
- Existing accounts are never accessed through a guest checkout. After the email is verified, **all matching historical orders** (paid, unpaid and failed) are linked to the customer.
- Existing Filament users retain admin access when the migration runs; new checkout customers cannot access `/admin`.
- Wholesale and influencer statuses are independent, can coexist, and are requested at `/account/roles`.
- Admins approve applications in Filament > Shop > Partner applications, or set both statuses in Shop > Customers.
- Approved verified influencers choose one permanent unique code. The default discount is 30%, adjustable by admins from 0 to 95%.
- Checkout coupons discount products only; shipping is calculated separately. The server uses current catalog prices and Stripe receives the discounted line prices.
- Approved status pages are `/account/wholesale` and `/account/influencer`. Wholesale pricing and influencer statistics are left for later.

### Deploy customer accounts

```bash
git pull origin main
php artisan migrate --force
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan test --filter=CustomerAccountsTest
```

SMTP must be configured for email verification and password reset. No extra workers are needed.

To grant admin access to a newly created Filament user, explicitly set its `is_admin` field to `true` (e.g. via `php artisan tinker`). This flag is **never** granted during customer checkout.


### Seamless post-payment onboarding

On the Stripe thank-you page, the customer sees a direct account button and is redirected after 5 seconds. The checkout stores a **separate, browser-only one-time activation token** in sessionStorage; the thank-you page exchanges it through `POST /account/complete-checkout`. Only a newly created passwordless account for that specific paid order can be authenticated using this proof. Missing/lost browser sessions do **not** redirect to login: `/account/checkout/{orderNumber}` shows only the current purchased order. Existing email accounts are never authenticated based solely on a new guest purchase; customers can create or reset a password by a secure email link.

Deploy migration `2026_10_15_000004_add_checkout_activation_token_to_orders.php` with `php artisan migrate --force` before testing a new checkout. Existing orders from before this migration do not have the one-time browser proof, so they use the safe current-order view.


### Account continuity and email verification

- Saving the first password **keeps the user signed in** and redirects straight to `/account`, even when the previous page was a login screen.
- A successfully used password-reset link automatically signs in and confirms the email address; the link itself proves mailbox ownership.
- Email verification links are temporary and cryptographically signed. They work in any browser without an existing session, then sign the recipient in automatically.
- After email confirmation, the app attaches **all** previously unassigned orders with the same normalized email address (including unpaid and failed orders). It never takes orders already assigned to another user.
- New customers can see their current attached paid order even before confirming email, but older unassigned orders remain hidden until email ownership is proven.
- Guest requests to customer-only pages redirect to `/account/login`; no accidental Filament login redirects.
- Test with `php artisan test --filter=CustomerAccountsTest`.


### Payment email receipts and guest order links

Payment confirmation, failed-payment and next-day reminder emails show the saved order's products, variants, quantities, unit/line prices, subtotal, discount, delivery and total. Each email contains **Voir ma commande** linked to `/account/checkout/{orderNumber}?token=...`. This is a private bearer link: it displays only that order to anyone with the link, without signing them into an account. Do not publish or expose the token. On unpaid or failed orders, the details screen provides the existing Stripe payment retry. The original `/order/{orderNumber}?token=...` link also redirects paid orders to the full order details, rather than to the temporary Stripe thank-you page. No migrations or new workers are needed for this change.


### Transactional email design

All customer emails now use one responsive, inline-styled **French BLACK MILK** design:

- Order success, failure, and one-time reminder: `resources/views/emails/payment-status.blade.php`.
- Email verification and password creation/reset: `resources/views/emails/account-action.blade.php`.
- Shared logo/header, paper background and footer: `resources/views/components/mail/layout.blade.php`.
- Shared button: `resources/views/components/mail/button.blade.php`.
- Email palette: `config/email.php` (keep it aligned with the Tailwind tokens in `resources/css/app.css`).

Laravel's built-in signed verification and password-reset notifications are customized in `AppServiceProvider::boot()`. No new queues, workers, frontend build or migrations are required. Existing SMTP settings and secure link expiry remain unchanged.

Deploy and verify:

```bash
git pull origin main
php artisan optimize:clear
php artisan test --filter=BrandedEmailsTest
php artisan test --filter=PaymentEmailsTest
```

Send a new verification or password-reset email to check the styling. Previously delivered messages cannot change.


## Filament shop dashboard overview

The main Filament dashboard (/admin) auto-discovers
`app/Filament/Widgets/ShopOverviewStats.php`. It shows three compact stats:

- **Partner applications**: all `role_applications.status = pending`.
- **Paid orders pending**: `orders.status = pending` **and**
  `orders.payment_status = paid`. Unpaid checkouts are excluded.
- **Sales this month**: number of orders with `payment_status = paid`
  and `paid_at` in the current `Europe/Paris` calendar month. The
  in-card Filament sparkline displays daily counts through today, including
  zero-sale days and handling UTC/Paris month boundaries.

Cards link to their corresponding admin resource. Data refreshes every
60 seconds. The area chart uses the native Filament stats chart and
brand-colored styling in `resources/css/filament/admin/theme.css`.

Directly below the stats, the main dashboard shows the **Maison Plush-style
orders widget** (`app/Filament/Widgets/OrdersWidget.php` and
`App\\Livewire\\Admin\\OrdersWidget`). The filter pills prioritize payment
(`unpaid`, `paid`, `processing`, `failed`) before fulfillment
(`pending`, `processing`, `shipped`, `completed`, `cancelled`).
The payment badge is the prominent column; fulfillment only becomes actionable
for paid orders, except for cancelled orders. Filter counts cover the entire
database; the table shows the latest 60 matching orders. Only Filament admins
can mount the underlying Livewire component.


Deploy (the theme CSS requires a frontend rebuild):

```bash
git pull origin main
npm run build
php artisan optimize:clear
php artisan test --filter=ShopDashboardStatsTest
php artisan test --filter=DashboardOrdersWidgetTest
```

## Telegram notifications for paid orders

The verified Stripe payment transition creates exactly one row in `order_telegram_notifications` per order.
Immediately after the Stripe HTTP response, Laravel attempts the **first Telegram notification** for a newly paid order, without waiting for cron. The hourly Artisan command retries unsuccessful first sends **at any time of day**. A separate scheduler task sends clearly identified **forgotten/unprepared order reminders at 10:00 on Monday–Friday, in `Europe/Paris`**, while `payment_status=paid` and the order `status=pending`. The first reminder is eligible at least 24 hours after the initial successful notification; subsequent reminders are eligible on the next working-day morning (at most once per Paris calendar day). Reminders stop when fulfillment status changes. Unpaid orders and orders paid before this feature was deployed are not automatically enrolled.

The database records `first_sent_at`, `last_sent_at` and a short-lived `claimed_at` sending lock. Initial Telegram send failures remain eligible for the next hourly retry; failed reminders are retried at the next eligible workday 10:00 slot. Pending tracked order reminders already in the database automatically adopt the new schedule; **paid-order reminders do not use Laravel's jobs queue**, so no migration or queue flush is required. Changing the schedule does not send stale reminders overnight. Messages include each ordered product, saved variants, quantities, prices, discounts, customer contact details, full shipping destination, pickup point when applicable, delivery method, customer note, and totals. They link to the authenticated order page on the store website as **Voir la commande sur le site** (without mentioning framework names), never exposing the private customer order token.

### 1. Create a Telegram bot and get the chat ID

- In Telegram open **@BotFather**, run `/newbot` and copy the bot token.
- Start a private chat with the bot and send `/start`, or add the bot to the intended Telegram group and send a message mentioning the bot.
- To find the chat ID, visit `https://api.telegram.org/bot<TOKEN>/getUpdates` with your actual token. Read `result[*].message.chat.id` (group IDs are typically negative). If a group does not appear, mention the bot in the group or disable its privacy mode in BotFather and send another message. Keep the token private.

Set these on the production server in `.env`:

```dotenv
TELEGRAM_BOT_TOKEN=123456789:AA_example_replace_me
TELEGRAM_CHAT_ID=-1001234567890
```

Do not put the real token in Git.

### 2. Deploy with the existing one-minute scheduler cron

The existing system crontab is enough for Telegram orders, applications, and
the database queue worker. **Do not add a separate hourly Telegram cron.**

```cron
*/1 * * * * cd /var/www/blackmilkfrance.com && php artisan schedule:run >> storage/logs/scheduler.log 2>&1
```

Run after each deployment:

```bash
cd /var/www/blackmilkfrance.com
git pull origin main
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan schedule:list
```

Scheduler tasks:

- `orders:telegram-notifications --initial-only`: hourly retries of failed
  first paid-order notifications, including nights and weekends.
- `orders:telegram-notifications --reminders-only`: Monday–Friday at 10:00
  `Europe/Paris`, for tracked paid orders still awaiting preparation.
  The command itself also refuses reminders outside that window, including
  when invoked manually with no options.
- `partners:telegram-notifications`: every five minutes, retries failed
  first partner alerts directly and enqueues eligible reminders.
- `queue:work database --queue=partner-telegram --stop-when-empty --max-time=45 --tries=3`:
  runs once per minute via Scheduler to drain **only** partner reminders. No
  additional cron line, Supervisor daemon, or manual queue worker is needed.
  Requires the `jobs` table (already supplied by Laravel's standard migration).
  The partner job explicitly uses the database connection, even if another
  queue connection is configured as the application default.

### 3. Partner Applications notifications

A verified customer submitting a new request from `/account/roles`
triggers an **immediate, synchronous** Telegram message to the same bot/chat
as paid-order notifications. No queue is involved in the first send.
A Telegram outage never blocks saving the application.

The message shows the partner program (wholesale/influencer), applicant name,
email, submission time, full applicant message, and the authenticated admin
link `/admin/role-applications/{id}/edit`.

Failed first sends are retried synchronously by `partners:telegram-notifications`.
Existing pending requests created before deploying this feature are picked up
on the first scheduler run; approved/rejected requests are never sent.

If an application stays `pending` for at least 24 hours after its last
successful Telegram notification, the scheduler enqueues a **clearly marked
reminder**. A database job sends it, and the next reminder is eligible only
after another 24 hours. Atomic database claims and queued timestamps prevent
overlapping cron invocations from sending repeated alerts. Before sending, the
job verifies that the application still has `status=pending`; approving or
rejecting a request stops reminders immediately.

The `role_applications` table stores
`telegram_first_sent_at`, `telegram_last_sent_at`,
`telegram_claimed_at`, and `telegram_reminder_queued_at`.

### 4. Verify

```bash
php artisan schedule:list
php artisan partners:telegram-notifications
php artisan orders:telegram-notifications --initial-only
php artisan schedule:list
php artisan test --filter=PartnerApplicationTelegramNotificationsTest
php artisan test --filter=OrderTelegramNotificationsTest
```

Telegram failures are logged to `storage/logs/laravel.log`. If a queue
job fails three times, inspect `php artisan queue:failed`. The next scheduler
scan can re-enqueue a pending reminder after its 20-minute dispatch lock expires.

### Sendcloud status transitions

- When a Sendcloud label is created and a tracking number is returned, the order is marked `shipped`.
- A signed Sendcloud parcel webhook with tracking or a shipped/in-transit event also marks it `shipped`.
- A signed delivery event marks it `completed`. Once completed, a later carrier event cannot revert it to shipped.


## Customer account preview from Filament

Filament **Shop → Customers** has a **Login as user** row action (eye icon) for ordinary customers. Only a currently authenticated `is_admin` user may start a preview; administrators and Warehouse staff cannot be impersonated. The action asks for confirmation and opens the selected customer's actual account at `/account`, including its real order detail pages.

The customer preview is **read-only**. A prominent persistent banner identifies the account and provides **Return to admin** (POST with CSRF). Every non-GET request is blocked server-side except this return action. A strict allowlist also prevents state-changing GET flows (verification, payment completion, checkout onboarding), and the normal order-claiming code is bypassed. For verified accounts, any unclaimed order matching the customer's email is displayed without assigning ownership in the database; other customers' orders remain inaccessible.

Switching into and out of preview invalidates and regenerates the session ID, stores the original admin ID and password hash fingerprint only in the server-side session, and logs start/end actions. Returning requires the original user still to be an administrator whose password hash is unchanged. The preview uses the existing web guard and cannot access the Filament panel until restored. No new database table or dependency is required.

Test with `php artisan test --filter=CustomerPreviewTest`. In the same browser, other admin tabs share the switched session until **Return to admin** is pressed.

## Sendcloud v3 tracking history

The account order list and detail page show a French tracking accordion beneath "Numéro de suivi", with the existing Motion animation. The display includes five milestones (label created, accepted, in transit, out for delivery, delivered), confirmed event times and the actual carrier history. Future stages are inactive; carrier descriptions are displayed as received.

Historical events are stored in the order_tracking_events table with stable deduplication. Orders cache the last tracking phase, sync time and expected delivery date. The frontend only reads local data, and never calls Sendcloud while rendering an account page.

Initial historical import (also includes completed and archived orders):
    php artisan migrate --force
    php artisan shipping:sync-tracking --all

Sample and individual repair:
    php artisan shipping:sync-tracking --all --limit=5
    php artisan shipping:sync-tracking --order=BM-YYYYMMDD-XXXXXX

Tracking responses may have the documented top-level events list, a nested parcel/data object, or an announced parcel whose events array is missing or null (carrier has not scanned it). The sync service accepts these cases without inventing carrier events. Unknown 200 responses are rejected with a **keys-only, sanitized** error that helps diagnose unexpected upstream schemas without logging customer data. To diagnose a single failing shipment after deployment, run:
    php artisan shipping:sync-tracking --order=BM-20261104-000014

Sendcloud API v3 supports GET /api/v3/parcels/tracking/{tracking_number} to retrieve tracking events by number. It does not document a batch history fetch for multiple numbers, so the progress-bar command handles them sequentially with an adjustable delay. Upserts are idempotent, errors are reported per order, and HTTP 429 stops the run.

Existing signed Sendcloud parcel_status_changed webhooks trigger a history refresh. The Laravel scheduler also runs shipping:sync-tracking hourly for non-terminal paid shipments if the previous sync is at least 50 minutes old. Only an actual delivered phase marks an order completed, and archived/cancelled/completed statuses are never regressed.

Tests: php artisan test --filter=SendcloudTrackingHistoryTest
Also run: php artisan test --filter=SendcloudWebhookTest
And: php artisan test --filter=CustomerAccountsTest

## Warehouse Stripe refunds

Warehouse staff with `is_warehouse` or `is_admin` can refund a paid Stripe order from its detail page. The English-only mobile-first bottom sheet supports **several products in one refund**, with an explicit checkbox and independent quantity stepper in every selected product row. The alternative refunds the entire *remaining* order payment. Selected-product refunds issue **one Stripe refund** for the combined server-calculated amount using the exact discounted unit prices saved for Checkout; shipping is excluded. Refunding the remaining balance includes shipping and any not-yet-refunded funds. The final confirmation displays an itemized list with quantities, line totals and the exact refund amount.

The flow requires a separate review screen, the exact refund amount and an explicit acknowledgment before the final action. Server validation recalculates all amounts; it never trusts browser-submitted prices. A row lock on the order prevents concurrent refunds, and a persistent UUID-based Stripe idempotency key prevents duplicate refunds. Every attempt is stored in `order_refunds` with staff user, amount in cents, Stripe ID and outcome. Multi-product refunds store an `items` JSON snapshot (original item index, quantity, discounted unit cents, product name) while legacy single-item refunds remain supported. The additive migration `2026_11_09_190000_add_items_to_order_refunds_table.php` preserves existing refund history. Pending or uncertain responses keep the amount reserved for review; never retry with a new key while Stripe's result is uncertain. Stripe `refund.created`, `refund.updated`, and `refund.failed` webhooks update tracked refund outcomes. Pending and uncertain entries also provide a **Check status in Stripe** action that fetches the refund or lists PaymentIntent refunds without creating another charge reversal. Enable these three event types on the existing Stripe webhook endpoint (`/stripe/webhook`) in the Stripe Dashboard; otherwise pending refunds will not automatically reconcile.

When the total payment is refunded or reserved (whether at once or through item refunds), the order remains visible under **All orders** and **Refunds**, but leaves the actionable **To prepare** / **Preparing** filters, prevents further fulfillment actions and stops its Telegram pending-order reminders. Existing customer payment/fulfillment status fields are intentionally unchanged; the staff view shows the refund history separately. Refunds initiated outside Warehouse (directly in Stripe) are not imported into this history.

Deploy with:

```bash
php artisan migrate --force
npm run build
php artisan optimize:clear
php artisan test --filter=WarehouseRefundTest
php artisan test --filter=WarehousePwaTest
```

## Warehouse PWA — staff order preparation

A separate minimal mobile-first staff application is available on the shop
domain. It has its own login and **14-day session** (cookie path `/warehouse`,
cookie `bmlk_warehouse_session`). This does not extend customer or Filament
sessions. Everything is protected by the normal Laravel authentication system,
CSRF verification and a specific permission (`users.is_warehouse`, or
`is_admin`). Staff can browse all orders, including unpaid, failed, shipped, cancelled, archived and refunded orders. Only paid, not fully refunded orders can be prepared or shipped; unpaid and failed orders are read-only.

- `/warehouse/` — all orders with payment and fulfillment status badges, photos and quantities,
  search, separate status and payment filters, default newest-first ordering and quick access to every order. The bottom counter tracks paid orders still awaiting preparation.
- `/warehouse/orders/BM-YYYYMMDD-XXXXXX` — item-first detail screen, packing
  actions, Sendcloud label purchase and printable PDF, address and contacts.
- `/warehouse/login` — standalone staff login; direct order links preserve
  the intended destination after successful authentication.

Telegram **paid-order alerts and reminders** now link directly to the
warehouse detail screen, not the Filament edit form. Older Telegram messages
retain their original URL.

Install the PWA on iPhone with **Safari → Share → Add to Home Screen**.
On Android, use the browser's **Install app** option. The manifest, icon,
and service worker live in `public/pwa-warehouse/`. The service worker is served via Laravel at `/warehouse/sw.js`.
Keep static assets in `public/pwa-warehouse` to avoid Nginx's
`try_files $uri $uri/` resolving `/warehouse` as a physical directory
and returning 403. The service worker uses network-only navigation: customer addresses, labels and order details are
**never stored in an offline cache**. HTTPS is required for PWA installation.

Warehouse order details display a tracking number above the Items section once assigned. The copy button provides a green check and `Copied` feedback, including a fallback for iOS webviews. A separate **Track shipment** link appears at the bottom when Sendcloud has saved a valid HTTPS `tracking_url`; no tracking URL is fabricated.\n\nThe order label uses the existing Sendcloud v3 `createLabel` implementation
with its existing lock to avoid duplicate label purchases. The purchase
requires an explicit confirmation. As in the Filament resource, Sendcloud
creation marks the order `shipped` when it returns a tracking number.
Once the PDF is available the primary action opens the label in the browser
PDF viewer for printing, without generating a second paid label. For orders
outside the supported EU flow, the label must be handled in Sendcloud.

### Warehouse staff access

After deployment, you can sign into the PWA using an existing Filament admin
account. For a warehouse-only employee, give an existing account with a
**password** explicit warehouse permission (without admin access):

```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'employee@example.com')->firstOrFail();
$user->forceFill(['is_warehouse' => true])->save();
```

If the employee does not yet have an account, create one with a password,
then enable this flag. Normal customer accounts are denied access even if
they know a valid order number.

### Deploy / verify

```bash
cd /var/www/blackmilkfrance.com
git pull origin main
php artisan migrate --force
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:list --path=warehouse
php artisan test --filter=WarehousePwaTest
php artisan test --filter=OrderTelegramNotificationsTest
```

The existing one-minute cron is unchanged. Opening an order directly from
the Telegram app can initially use the Telegram in-app browser rather than
the installed standalone PWA; it still reaches the same protected order
after staff login in that browser.

