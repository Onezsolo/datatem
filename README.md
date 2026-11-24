# Datatem — Data, Services & Super Mall Template

Production-ready Laravel 11 template for a Ghana-focused digital services business: data bundles, WAEC PINs, utility bills, subscriptions, AFA registration, wallet, referrals/reseller tools and a local shopping module (Super Mall). Shop and services share one account, one wallet and one admin console.

## Stack

- PHP 8.3, Laravel 11, MySQL 8 (SQLite in tests)
- Vite 8, Tailwind CSS 4, Alpine.js 3, Instrument Sans
- PHPUnit 10
- External service provider API for data/services (credentials required); Super Mall stays local

## Local setup

```powershell
composer install
copy .env.example .env
# For local development change: APP_ENV=local, APP_DEBUG=true, SESSION_SECURE_COOKIE=false, APP_URL=http://localhost
php artisan key:generate

$env:PATH = 'C:\laragon\bin\nodejs\node-v22;' + $env:PATH
npm install
npm run build

php artisan migrate
php artisan db:seed            # local/testing: admin@example.com / admin123 + demo user + demo catalog
php artisan storage:link
php artisan serve
```

Tests use SQLite `:memory:` and never touch the configured database.

## Commands

```powershell
npm run build
npm run dev
php artisan test
php artisan route:list
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Production checklist

1. `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain`, `APP_TIMEZONE=Africa/Accra`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`.
2. Generate `APP_KEY` once and keep it secret; never reuse another installation's key.
3. Create the first admin with seed environment variables:
   `SEED_ADMIN_EMAIL`, `SEED_ADMIN_PASSWORD`, `SEED_ADMIN_NAME`, `SEED_ADMIN_PHONE`, then run `php artisan db:seed --class=UserSeeder --force`.
   Demo data is not seeded outside local/testing (`SEED_DEMO_CATALOG=true` only if you want the sample shop).
4. Set provider credentials: `PATUPAA_API_*` (data/services) and `PAYSTACK_*`; `REFER2BUNDLE_*` only if the legacy reseller storefront is used.
5. Point the web server document root at `public/`, enable HTTPS, and run `php artisan storage:link`.
6. Deploy assets: `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
7. Cache: `php artisan config:cache && php artisan route:cache && php artisan view:cache`. Clear with the matching `:clear` commands after env changes.
8. Schedule `php artisan schedule:work` via cron (`* * * * * cd /path && php artisan schedule:run`) and run a queue worker if you switch `QUEUE_CONNECTION` from `sync`.
9. Back up the MySQL database and `storage/app` (uploads, provider assets, logs policy) daily.
10. Rotate provider/admin credentials before handing the template to a new owner; delete demo accounts and change seeded passwords.

## Security defaults

- `/admin/*` requires the r2b login and an admin role; guests are redirected, non-admins get 403.
- All wallet purchases are login-only; shop/services duplicate submissions are blocked by idempotency keys + atomic locks (replayed requests return the original order, never double-charge).
- Login/registration, service purchases and checkout are rate-limited; provider webhooks are signature-checked.
- Security headers (nosniff, frame options, referrer policy, permissions policy, HSTS over HTTPS) are applied globally; HTTPS and destructive DB commands are enforced in production.
- `.env`, `vendor/`, build output, provider catalog copies and uploads are excluded from git.

## Structure

- `app/Http/Controllers` — web/API controllers (shop, services, admin, auth)
- `app/Services/Patupaa` — provider catalog sync, order submission, availability settings
- `app/Shop` — local Super Mall module (models, services, mail)
- `app/Support` — `Brand` and `Navigation`
- `resources/views/components` — design-system components and shells (public, member, admin)
- `docs/design-system.md` — tokens, components, navigation model
- `docs/superpowers/plans` — phased implementation plans

## Operational notes

- Provider catalog availability is mirrored: items missing at the provider are disabled locally; admins can additionally force any item/service off in `/admin/services`.
- Service purchases debit the wallet first and auto-refund on provider failure; finance totals (data + shop + services) live in `/admin/statistics`.
- The legacy `/bundles` URL redirects to the provider-backed data page.
## Features, design and service sources

- **Features:** Admin → Settings → Features toggles modules (Super Mall, data, WAEC, utility, subscriptions, AFA, orders, wallet, referral, reseller, support, notifications). Disabled modules vanish from public/member navigation and return 404 until re-enabled; the admin console stays available.
- **System design:** Admin → Settings → System design offers five WCAG-checked palettes (Navy & Gold, Emerald & Brass, Graphite & Ember, Ocean & Steel, Plum & Rose). The selection is stored in settings and applied through CSS variables on every layout.
- **Service sources:** Admin → Services → Provider sources lets you add/edit/delete compatible API sources, choose which services each source serves, pick the default, and sync each source independently. Credentials are encrypted at rest and keys are masked in the UI. The default source is bootstrapped from `PATUPAA_API_*`; `php artisan db:seed --class=ServiceProviderSeeder` attaches legacy catalog rows to it.
- **Accounting safety:** Catalog items and orders record their source; purchases debit the wallet and call only the item’s source; duplicate submits are blocked by idempotency keys and locks.
## Developer account and template licensing

- Create or rotate the developer account:

```powershell
php artisan template:dev-account dev@yourdomain.com --password="StrongPassword" --name="Template Developer"
```

- For fresh installs set `SEED_DEVELOPER_EMAIL`, `SEED_DEVELOPER_PASSWORD`, `SEED_DEVELOPER_NAME`, `SEED_DEVELOPER_PHONE` before running `php artisan db:seed`.
- The developer signs in and lands on `/developer`: issue license keys to clients (client, email, domain, agreement reference, expiry, notes), revoke/reinstate them, and record the deployment license for this installation. Keys are stored hashed and shown only once.
- Developers can also open the admin console and its settings/services pages. Licensing is administrative tracking for your agreements; it does not hard-lock the application — add an enforcement policy in your agreement if required.
### Developer control, template packages and handover

- **Developer-only settings:** service provider sources, provider credentials, service switches and the AFA price are restricted to the developer account (admin APIs return 403 and the admin UI hides the sections). Client admins still manage catalog availability, features, design, payments and the rest of the business configuration.
- **Template packages:** the developer configures this deployment (design, features, service switches, AFA price, provider sources), saves it as a package in `/developer/packages`, and applies it to another deployment with one click. Business data is never packaged.
- **Handover:** after applying the package and setting an active deployment license, the developer types HANDOVER to complete handover. This permanently disables developer login, `/developer` and developer access to the admin console on that deployment; the client admin continues running the business. Handover cannot be undone from the deployment.
### Web templates and GitHub delivery

- Add `GITHUB_TOKEN` (repo scope / fine-grained: repository creation + contents write) to `.env`; optional `GITHUB_API_URL` for GitHub Enterprise.
- Developer → **Web templates** registers template projects from a local git path or a git URL (branch + version).
- Per template, enter the client GitHub owner, repository, branch, visibility and a note; **Push** creates the repository when missing, clones the source, pushes the branch and records the result with the repository URL. Failed pushes are logged with tokens redacted.
- Source paths must be git repositories on the server. This console is developer-only and is disabled after handover like the rest of the developer tools.
### Template products (five template setups)

Developer → **Template products** ships five individual template setups, each with its own modules, provider services and design:

| Product | Modules | Services | Design |
|---|---|---|---|
| Data + Subscriptions + Mall | Shop, shop orders, data, subscriptions, service orders, data orders, wallet, notifications, support | Data bundles, digital | Navy & Gold |
| Data + Subscriptions | Data, subscriptions, service orders, data orders, wallet, notifications, support | Data bundles, digital | Emerald & Brass |
| Mall only | Shop, shop orders, wallet, notifications, support | None | Ocean & Steel |
| Subscriptions only | Subscriptions, service orders, wallet, notifications, support | Digital | Plum & Rose |
| Data only | Data, service orders, data orders, wallet, notifications, support | Data bundles | Graphite & Ember |

Each product has its **own console path** (/developer/products/{product}) and a **preview path** (/developer/products/{product}/preview). Opening, previewing or creating a package from a product never changes the current deployment. Create a client package from the product, then apply that package on the client's deployment. Custom combinations can also be captured as template packages.
### Template catalog structure (provider-style)

The developer catalog follows the structure used by established template providers (Envato-style licenses, Creative Tim/SaaSykit product pages):

- **Product page:** category, version badge, tagline, highlights, live preview, documentation link (`TEMPLATE_DOCS_URL`), feature matrix, what's included, support/update policy, changelog and the product's individual paths.
- **License tiers:** each product has **Standard** (1 deployment, 180 days support, no resale) and **Extended** (unlimited deployments under agreement, 365 days support, resale/client billing allowed) pricing in `config/template_products.php`.
- **Licensing:** issuing from a product/tier stores product key, tier, price, deployments allowed and support end date on the license; admins cannot access the developer catalog or license tools.
- **Delivery:** "Create client package" snapshots the product into Template packages for applying on client deployments, followed by deployment license and handover.
### Service sources are a developer page

Provider sources, service switches and AFA pricing live at **`/developer/services`** in the developer console. The admin **Services** page (`/admin/services`) now contains only the catalog mirror (search, sync, per-item availability) and never exposes provider credentials or global service switches.
## Security and schema hygiene

- Passwords: minimum **6 characters**, enforced by server validation (`register`, admin user changes) and client `minlength` hints. Raise this value in the validators if the agreement requires a stronger policy.
- API requests are globally rate-limited (120/min) with stricter limits on login, registration, OTP, checkout and service purchases. Secrets are never returned by the settings API; provider credentials are encrypted and masked.
- Authorization is server-side throughout: member routes require the authenticated web session, `/admin/*` and admin APIs are admin-only, developer tools and service management live under `/developer` with a developer role. Client-side hiding is never the only protection.
- Legacy shop admin tables/columns (`shop_admins`, `shop_admin_audit_logs`, `admin_id` on inventory/notes) were removed; admin identity is the unified `users.role` model. Dead legacy views (`bundles`, classic `layouts/app`) were deleted.
- Service management APIs live at `/api/developer/services` (developer token only); the old `/api/admin/services` path is gone.