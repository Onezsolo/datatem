# One System — Foundation (Phase 0) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn `datatem` (r2b reseller base) into the frontend foundation of one complete system: compiled Tailwind 4 design system, unified public/member shells, and four pilot pages rendered with one uniform UI/UX.

**Architecture:** Keep the Laravel 11 r2b base and its JWT API contracts. Adopt the novagel design system (navy `#001534` / yellow `#FFCC00` / red `#E63946` tokens, Instrument Sans, Blade UI components) as the single visual language. Add a Vite 8 + Tailwind 4 pipeline and bundled Alpine, then migrate pages page-by-page behind new Blade shells. Legacy pages keep working through a temporary palette bridge (static `primary-*` aliases) until later phases restyle them.

**Tech Stack:** Laravel 11.48, PHP 8.3.33, Composer 2.10, Node v22.23.2 (`C:\laragon\bin\nodejs\node-v22`), Vite 8, Tailwind CSS 4, Alpine.js 3, PHPUnit 10 (SQLite `:memory:`).

**Spec:** `novagel/docs/design-system.md`, `novagel/README.md`, `novagel/PATU_CONTEXT.md` (user rules: no gradients, no pill shapes, no decorative emoji/Unicode, functional UI only), and the user brief: "one complete system using a uniform design and UI/UX" with r2b as base.

## Global Constraints

- Base stays Laravel 11 + existing r2b APIs, JWT auth and DB tables; no destructive schema changes in Phase 0.
- Palette (verbatim): navy `#001534`, navy-soft `#0B2550`, yellow `#FFCC00`, yellow-strong `#E6B800`, red `#E63946`, red-strong `#B0202D`, red-strong-hover `#941B26`, surface `#FFFFFF`, surface-muted `#F4F4F4`, ink `#0B0B0B`, muted `#5A6270`, line `#E2E4E8`, nav-muted `#D7DEEA`.
- Radius: control `0.375rem`, card `0.75rem`. Container `75rem`, inline padding `1rem`. Breakpoints 40/48/64/80/96rem.
- Typography: Instrument Sans; display 40/800, heading 24/800, body 16/400, small 14/400.
- WCAG AA for all text pairs; `brand-red` only for non-text accents, `brand-red-strong` for text-bearing danger.
- No gradients, no pill controls, no glow effects, no decorative Unicode/emoji.
- GHS currency, `Africa/Accra`, English (Ghana-first).
- Migrated pages must not load `cdn.tailwindcss.com`, `unpkg.com`, or `fonts.googleapis.com`.
- Never commit `.env`, `vendor/`, `novagel/`, storage logs or compiled caches. Tests use SQLite `:memory:` only; never run migrations against the production MySQL configured in `.env`.
- Use Node from `C:\laragon\bin\nodejs\node-v22` by prepending it to `PATH` in every npm command.

## Review Focus

1. **Tailwind CDN → compiled CSS:** classes assembled dynamically in Alpine/JS strings or injected via `tailwind.config` (`primary-*`, gradients) silently disappear. Each migrated page must be built and checked for the palette utilities it uses; legacy pages get static `primary-*` aliases in Task 6.
2. **Alpine instances:** loading bundled Alpine and the CDN build together double-initialises stores and breaks `x-data`. New shells use only bundled Alpine; the legacy layout switches to `legacy.js` in Task 6.
3. **Route-name collisions:** `home`, `login`, `orders.*`, `products.*`, `categories.*` will be reintroduced by the shop port. Duplicate names break `route()`/`url()`; Task 1 adds a duplicate-name test that runs on every build.
4. **Brand settings vs fixed tokens:** `SettingsService` site name/logo must render in shells; runtime theme colors/gradients are retired, so no view may depend on `ThemeColorService` output after Task 6.
5. **Asset manifest missing:** `@vite` throws without `public/build/manifest.json`; views must guard the directive, and every verification run builds assets before loading pages.

---

## Program Roadmap

| Phase | Scope | Produces |
|---|---|---|
| **0 (this plan)** | Build pipeline, tokens, components, public/member shells, pilots (`/`, `/bundles`, `/login`, `/signup`), legacy palette bridge | One uniform UI foundation, working app |
| 1 | Restyle remaining member pages (dashboard, orders, topup, referral, reseller, profile, notifications, support) + auth flows | Uniform member area |
| 2 | Port novagel shop **into the data system**: `shop_*` tables (prefix avoids collisions with existing `orders`/`payments`/`users`), catalog, cart, guest checkout, Paystack orders, tracking, unified member Orders view (Data / Shop tabs), admin catalog | Unified commerce + bundles |
| 3 | Unify reseller storefront (`/store/{slug}`) with the shop shell and design language | One storefront system |
| 4 | Admin back office: single shell/nav (Dashboard, Bundles, Shop catalog, Orders, Top-ups, Payments, Users, Resellers, Affiliates, Finance, Support, Settings), server-side gating of `/admin`, finance ledger | Uniform admin |
| 5 | QA, accessibility sweep, performance, production build/deploy, operations docs | Shippable release |

**Locked decisions:** shop tables use `shop_*` prefix; admin/auth unification and MFA are decided in Phase 4 (out of Phase 0 scope); fixed brand palette replaces runtime theme colors; existing r2b APIs keep contracts; new pages are server-rendered Blade + Alpine, not SPA-only.

**Navigation model (user requirement):**
- Desktop: persistent left **sidebar** for member and admin areas (grouped: Overview, Shop, Data, Account); public area uses the top nav.
- Mobile: **bottom navigation** on every public/member page — Home, Shop, Bundles, Account (cart badge on Shop; Account opens member dashboard/login).
- Shop and data are one system: same shell, same account, same cart badge, unified Orders page with Data / Shop tabs, one checkout entry point.

---

### Task 1: Test harness and named-route baseline

**Files:**
- Create: `phpunit.xml`, `tests/TestCase.php`, `tests/Feature/PageSmokeTest.php`
- Modify: `routes/web.php`

**Interfaces:**
- Produces: route names `home`, `login`, `signup`, `bundles`, `support`, `password.request`, `topup`, `notifications`, `profile`, `legal.privacy`, `legal.terms` used by all shells/components.

- [x] **Step 1: Create the PHPUnit harness**

`phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php" colors="true">
    <testsuites>
        <testsuite name="Feature"><directory>tests/Feature</directory></testsuite>
        <testsuite name="Unit"><directory>tests/Unit</directory></testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
    </php>
</phpunit>
```

`tests/TestCase.php`:

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
}
```

- [x] **Step 2: Write the failing smoke test**

`tests/Feature/PageSmokeTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PageSmokeTest extends TestCase
{
    public function test_home_route_name_exists(): void
    {
        $this->assertSame('/', route('home', absolute: false));
    }

    public function test_public_pages_render(): void
    {
        foreach (['/', '/bundles', '/login', '/signup', '/support'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_route_names_are_unique(): void
    {
        $duplicates = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->duplicates();

        $this->assertCount(0, $duplicates, 'Duplicate route names: '.$duplicates->implode(', '));
    }
}
```

- [x] **Step 3: Run it and confirm failure**

Run: `php artisan test --filter=PageSmokeTest`
Expected: FAIL — no route named `home`.

- [x] **Step 4: Add names in `routes/web.php`**

Name the existing routes only; do not change paths or handlers. Exact names: `/`→`home`, `/login`→`login`, `/signup`→`signup`, `/bundles`→`bundles`, `/support`→`support`, `/forgot-password`→`password.request`, `/topup`→`topup`, `/notifications`→`notifications`, `/profile`→`profile`, `/privacy`→`legal.privacy`, `/terms`→`legal.terms`.

- [x] **Step 5: Run the suite**

Run: `php artisan test`
Expected: PASS (3 tests).

- [x] **Step 6: Commit**

```bash
git add phpunit.xml tests routes/web.php
git commit -m "test: add test harness and named route baseline"
```

---

### Task 2: Compiled frontend pipeline (Vite 8 + Tailwind 4 + bundled Alpine)

**Files:**
- Create: `package.json`, `vite.config.js`, `resources/css/tokens.css`, `resources/css/app.css`, `resources/js/app.js`, `resources/js/legacy.js` (stub in this task; real version in Task 4), `resources/js/components/carousel.js`, `resources/views/design-system.blade.php`
- Modify: `routes/web.php` (preview route), `.gitignore` (verify `/public/build` and `/node_modules` ignored — already true)

**Interfaces:**
- Produces: Tailwind utilities `bg-brand-navy`, `text-brand-ink`, `rounded-card`, `rounded-control`, `container-page`, `skip-link`, `carousel-*`; Vite entries `resources/css/app.css`, `resources/js/app.js`; `GET /design-system` (non-production only).

- [x] **Step 1: Add tooling manifests**

`package.json`:

```json
{
    "name": "datatem",
    "private": true,
    "type": "module",
    "engines": { "node": ">=20.19", "npm": ">=10" },
    "scripts": { "build": "vite build", "dev": "vite" },
    "devDependencies": {
        "@fontsource-variable/instrument-sans": "^5.2.5",
        "@tailwindcss/vite": "^4.0.0",
        "alpinejs": "^3.14.9",
        "laravel-vite-plugin": "^3.1.0",
        "tailwindcss": "^4.0.0",
        "vite": "^8.0.0"
    }
}
```

`vite.config.js`:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({ input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/legacy.js'], refresh: true }),
        tailwindcss(),
    ],
});
```

- [x] **Step 2: Tokens and base CSS**

`resources/css/tokens.css`: copy the 32-line token block from `novagel/resources/css/tokens.css` unchanged, then append the temporary legacy aliases used by Task 6:

```css
/* Legacy bridge: r2b views use primary-* and former CDN palette names. */
--color-primary-50: #eef2f7;
--color-primary-100: #dbe3ee;
--color-primary-200: #b9c8dd;
--color-primary-300: #8ba4c4;
--color-primary-400: #5d7fa6;
--color-primary-500: #375d88;
--color-primary-600: #001534;
--color-primary-700: #00112b;
--color-primary-800: #000e23;
--color-primary-900: #000a1a;
--color-mtn: #ffcc00;
--color-mtn-dark: #e6b800;
--color-airteltigo: #9333ea;
--color-airteltigo-dark: #6b21a8;
--color-telecel: #ff0000;
--color-telecel-dark: #cc0000;
```

`resources/css/app.css`:

```css
@import 'tailwindcss';
@import '@fontsource-variable/instrument-sans';
@import './tokens.css';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../views/**/*.blade.php';

@layer base {
    html { scroll-behavior: smooth; }
    body { @apply bg-surface font-sans text-ink antialiased; }
    a, button, input, select, textarea {
        @apply focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-navy;
    }
    @media (prefers-reduced-motion: reduce) {
        html { scroll-behavior: auto; }
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
}

@layer components {
    .container-page { width: 100%; max-width: 75rem; margin-inline: auto; padding-inline: 1rem; }
    .skip-link {
        position: absolute; top: -3rem; left: 1rem; z-index: 100;
        display: inline-flex; min-height: 2.75rem; align-items: center;
        border-radius: var(--radius-control); background: var(--color-brand-navy);
        padding-inline: 1rem; font-size: 0.875rem; font-weight: 700;
        color: var(--color-surface); transition: top 120ms ease-in;
    }
    .skip-link:focus { top: 1rem; }
    .carousel-track { scroll-snap-type: x mandatory; scrollbar-width: none; }
    .carousel-track::-webkit-scrollbar { display: none; }
    .carousel-item { scroll-snap-align: start; }
}
```

- [x] **Step 3: Entry scripts**

`resources/js/components/carousel.js`: copy from `novagel/resources/js/components/carousel.js` unchanged.

`resources/js/app.js` (Phase 0: Alpine + carousel only; stores are added in Task 4):

```js
import Alpine from 'alpinejs';
import { initCarousels } from './components/carousel.js';

window.Alpine = Alpine;
Alpine.start();
initCarousels();
```

`resources/js/legacy.js` (stub so the Vite entry exists; Task 4 replaces it with the stores version):

```js
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
```

- [x] **Step 4: Minimal preview route and view**

`routes/web.php`, inside `if (! app()->environment('production'))`:

```php
Route::view('/design-system', 'design-system')->name('design-system');
```

`resources/views/design-system.blade.php`: HTML shell that guards Vite like novagel (`@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))) @vite([...]) @endif`) and renders `<h1>Design system</h1>` plus token swatches using `bg-brand-navy`, `bg-brand-yellow`, `bg-brand-red` and `rounded-card`.

- [x] **Step 5: Build and verify**

Run:

```powershell
$env:PATH = 'C:\laragon\bin\nodejs\node-v22;' + $env:PATH
npm install
npm run build
```

Expected: `public/build/manifest.json` exists and lists `resources/css/app.css`, `resources/js/app.js`, `resources/js/legacy.js`. Then `php artisan test` PASS.

- [x] **Step 6: Commit**

```bash
git add package.json package-lock.json vite.config.js resources/css resources/js resources/views/design-system.blade.php routes/web.php
git commit -m "build: add vite tailwind pipeline and design tokens"
```

---

### Task 3: UI component library and design-system preview

**Files:**
- Create: `app/Support/Brand.php`, `tests/Unit/BrandTest.php`, `tests/Feature/DesignSystemTest.php`, `tests/Unit/DesignTokensTest.php`
- Copy + adapt: `resources/views/components/ui/{button,badge,card,carousel,icon,header}.blade.php`, `resources/views/components/layouts/app.blade.php`

**Interfaces:**
- Produces: `App\Support\Brand::name(): string`, `Brand::logoUrl(): string`, `Brand::faviconUrl(): string` (wrap `SettingsService`, fallback `config('app.name')` / `asset('assets/l1.png')`).
- Produces Blade components `x-ui.button` (variants primary|secondary|danger|outline|ghost|link), `x-ui.badge`, `x-ui.card`, `x-ui.carousel`, `x-ui.icon`, `x-ui.header` (props: `brandName`, `brandUrl`, `navigation` array of `{label,url}`, `cartUrl?`, `cartCount?`, `searchAction?`), `x-layouts.app`.

- [x] **Step 1: Write failing tests**

`tests/Unit/BrandTest.php`: with the SQLite `:memory:` database (no migrated tables, so `SettingsService` returns its fallbacks), assert `Brand::name()` equals `config('app.name')`, and `Brand::logoUrl()`/`Brand::faviconUrl()` return absolute URLs.

`tests/Feature/DesignSystemTest.php`: `GET /design-system` → 200, sees `Design system`, `Buttons`, `Badges`, and `nav` markup from `x-ui.header`; asserts the HTML contains `rounded-control` and does not contain `cdn.tailwindcss.com` or `unpkg.com`.

`tests/Unit/DesignTokensTest.php`: copy `novagel/tests/Unit/DesignTokensTest.php`; keep contrast assertions unchanged (navy/white 18.15:1 ±0.1, white on red-strong ≥ 4.5:1); it reads `resource_path('css/tokens.css')`.

- [x] **Step 2: Run and confirm failure**

Run: `php artisan test --filter='BrandTest|DesignSystemTest|DesignTokensTest'`
Expected: FAIL — missing classes/route/components.

- [x] **Step 3: Implement Brand and components**

`app/Support/Brand.php`:

```php
namespace App\Support;

use App\Services\SettingsService;

final class Brand
{
    public static function name(): string
    {
        return (string) SettingsService::get('site_name', config('app.name', 'Datatem'));
    }

    public static function logoUrl(): string
    {
        return (string) (SettingsService::getLogoUrl() ?: asset('assets/l1.png'));
    }

    public static function faviconUrl(): string
    {
        return (string) (SettingsService::getFaviconUrl() ?: asset('assets/l1.png'));
    }
}
```

Adapt the six UI components from novagel: keep variant/size class maps exactly; replace `config('store.name')` with `Brand::name()`; replace the fixed brand mark with `Brand::logoUrl()` (`alt=""`, wordmark text remains live); make `cartUrl`/`searchAction` nullable so links render only when provided; ensure all `route()` references point to the Task 1 names.

- [x] **Step 4: Update preview page**

`resources/views/design-system.blade.php` renders inside `<x-layouts.app>`: header with `navigation` = Bundles/Support items, button variants/sizes, badges, cards (default/muted), carousel with three items, and the contrast table from `docs/design-system.md`.

- [x] **Step 5: Build assets and run tests**

Run: `npm run build; php artisan test`
Expected: PASS, including token contrast tests.

- [x] **Step 6: Commit**

```bash
git add app/Support tests resources/views/components resources/views/design-system.blade.php
git commit -m "feat: port design system components and preview"
```

---

### Task 4: Public and member shells, navigation config, Alpine stores

**Files:**
- Create: `app/Support/Navigation.php`, `resources/views/components/layouts/public.blade.php`, `resources/views/components/layouts/member.blade.php`, `resources/views/components/shell/{public-header,public-footer,bottom-nav,member-sidebar,member-topbar}.blade.php`, `resources/js/stores/auth.js`, `resources/js/stores/alert-modal.js`, `resources/js/legacy.js`
- Modify: `resources/js/app.js`, `tests/Feature/DesignSystemTest.php`

**Interfaces:**
- Produces: `Navigation::public(): array<int, array{label: string, route: string|null, url: string|null, active: string}>`; `Navigation::member(): array` with same shape plus `icon: string|null`.
- Produces: `<x-layouts.public title?>` and `<x-layouts.member title?>` shells; `Alpine.store('auth')` and `Alpine.store('alertModal')` with the exact API of the legacy layout.

- [x] **Step 1: Extract legacy Alpine stores**

Copy the two store implementations from `resources/views/layouts/app.blade.php` (the `Alpine.store('auth', …)` object around L63 and the `Alpine.store('alertModal', …)` object around L165; exclude the `tailwind.config` assignment around L187 and all styling) into ES modules:
- `auth.js` exports an object with the same keys/behaviour (`token`, `user`, `isAuthenticated`, `storage`, `init`, `setAuth`, `clearAuth`, `logout`, `getToken`, `refresh`) — including the `auth_storage` localStorage/sessionStorage choice.
- `alert-modal.js` exports the existing `alertModal` store object (`open`, `close`, `message`, `title`, `type`).
Register them in `app.js` via `Alpine.store('auth', authStore)` / `Alpine.store('alertModal', alertModalStore)` before `Alpine.start()`.

`legacy.js` (used by the legacy layout in Task 6):

```js
import Alpine from 'alpinejs';
import authStore from './stores/auth.js';
import alertModalStore from './stores/alert-modal.js';

window.Alpine = Alpine;
Alpine.store('auth', authStore);
Alpine.store('alertModal', alertModalStore);
Alpine.start();
```

- [x] **Step 2: Navigation config**

`Navigation::public()` (Phase 0 items): Home (`home`), Shop (placeholder `url('/shop')` until Phase 2 adds `shop.index`), Bundles (`bundles`), Stores (`store.landing`), Support (`support`), Track (`login` until shop Phase 2 adds `orders.track`). `Navigation::member()` (sidebar): Overview → Dashboard (`url('/dashboard')`), Shop (`url('/shop')`), Buy data (`url('/bundles')`); Orders → Orders (`orders.index`); Earnings → Referral (`referral`), Reseller (`reseller`); Account → Wallet (`topup`), Notifications (`notifications`), Profile (`profile`), Support (`support`). Bottom navigation (public + member, mobile): Home, Shop, Bundles, Account.

- [x] **Step 3: Build shells**

`public` shell: skip link → `#main-content`, offline banner slot, `<x-shell.public-header>` with `x-ui.header` (brand from Brand, nav from Navigation), session status/warning strips, `<main id="main-content">{{ $slot }}</main>`, `<x-shell.public-footer>` (solid navy with 3px yellow top border, support/settings links, GHS note), `<x-shell.bottom-nav>` (Home/Bundles/Stores/Account), `@vite(['resources/css/app.css','resources/js/app.js'])` guarded by manifest check.

`member` shell: navy top bar with brand, page title slot, user placeholder, sign-out button that reuses the legacy logout routine (Bearer fetch to `/api/auth/logout`, clear `Alpine.store('auth')`, redirect to `/login`); **desktop sidebar** (fixed left, navy-on-surface, grouped nav from `Navigation::member()`, active = `request()->is(...)`); **mobile bottom navigation** (Home, Shop, Bundles, Account) instead of the sidebar; main content area with status/error banners, same guarded Vite include. Do not require a server-side authenticated user; member views hydrate from `Alpine.store('auth')`.

- [x] **Step 4: Extend tests**

DesignSystemTest: render a view using the public shell (preview page) and assert `Skip to main content`, `id="main-content"`, `Bundles`, `rounded-control`; assert absence of `cdn.tailwindcss.com`, `unpkg.com`, `fonts.googleapis.com`. Add a Navigation unit test asserting item counts and that every `route` name resolves via `route()`.

- [x] **Step 5: Build and test**

Run: `npm run build; php artisan test`
Expected: PASS.

- [x] **Step 6: Commit**

```bash
git add app/Support/Navigation.php resources/views/components resources/js tests
git commit -m "feat: add unified public and member shells"
```

---

### Task 5: Pilot pages — home, bundles, login, signup

**Files:**
- Modify: `resources/views/welcome.blade.php`, `resources/views/bundles.blade.php`, `resources/views/login.blade.php`, `resources/views/signup.blade.php`, `tests/Feature/PageSmokeTest.php`

**Interfaces:**
- Consumes: `x-layouts.public`, `x-layouts.member` (login/signup may use public shell with a centered auth card), `x-ui.*`, `Navigation`, `Alpine.store('auth')`.
- Preserves: every `fetch` endpoint, request body, storage key and redirect target currently used by these pages.

- [x] **Step 1: Strengthen tests (write first)**

In `PageSmokeTest`: for `/`, `/bundles`, `/login`, `/signup` assert 200, sees `Skip to main content`, and the HTML lacks `cdn.tailwindcss.com`, `unpkg.com`, `fonts.googleapis.com`. Additionally: `/login` sees `name="email"` and `name="password"`; `/signup` sees `name="phone"`; `/bundles` sees `x-data` and the three network names (`MTN`, `AirtelTigo`, `Telecel`); `/` sees `Buy data` and a link to `route('bundles')`.

- [x] **Step 2: Run and confirm failure**

Run: `php artisan test --filter=PageSmokeTest`
Expected: FAIL — legacy pages load Google Fonts/CDN and lack shell markers.

- [x] **Step 3: Migrate pages**

Rebuild each page's markup inside the public shell using only tokens and `x-ui.*` components; keep all `x-data`, `x-init`, `x-show`, `@click`, `$store.auth` and `fetch` code intact (move it into the page body). Remove per-page font/CDN `<link>`/`<script>` tags. Home: solid-color hero, quick actions (Buy data, Top up, Stores, Support), network badges using `bg-mtn`/`bg-airteltigo`/`bg-telecel`, features strip, support contacts from `SettingsService`. Bundles: network tabs, package cards (`x-ui.card`), buy buttons (`x-ui.button`), wallet balance strip. Login/signup: surface-muted page with white card, labels wired to inputs, visible inline errors, links to each other and `/forgot-password`.

- [x] **Step 4: Build, test, verify manually**

Run: `npm run build; php artisan test`
Then `php artisan serve` and check at 360px, 768px, 1280px: navigation, focus outlines, login POST still reaches `/api/auth/login`, bundles list loads and buy action still calls the existing endpoint.

- [x] **Step 5: Commit**

```bash
git add resources/views tests
git commit -m "feat: migrate pilot pages to unified design system"
```

---

### Task 6: Legacy layout palette bridge and docs

**Files:**
- Modify: `resources/views/layouts/app.blade.php`, `resources/js/legacy.js`, `routes/web.php` (none expected), `tests/Feature/PageSmokeTest.php`
- Create/modify: `docs/design-system.md` (adapted copy of novagel handoff), `README.md` (design/build section)

**Interfaces:**
- Consumes: `resources/js/legacy.js`, `resources/css/tokens.css` aliases (`primary-*`, `mtn*`, `airteltigo*`, `telecel*`).
- Produces: every `@extends('layouts.app')` page loads compiled CSS + bundled Alpine and renders with the uniform palette.

- [x] **Step 1: Write the bridge test first**

Add `LegacyLayoutTest`: `GET /dashboard` (extends `layouts.app`) → 200; HTML contains `build/assets`; lacks `cdn.tailwindcss.com`, `unpkg.com`, `fonts.googleapis.com`.

- [x] **Step 2: Run and confirm failure**

Run: `php artisan test --filter=LegacyLayoutTest`
Expected: FAIL — CDN links present.

- [x] **Step 3: Rewire the legacy layout**

In `layouts/app.blade.php`: remove Google Fonts links, Tailwind CDN script, Alpine CDN script, and the `tailwind.config` assignment; replace with the guarded `@vite(['resources/css/app.css','resources/js/legacy.js'])`. Keep the `x-cloak` rule and behaviour-only inline styles; replace `.gradient-text` with solid `color: var(--color-brand-navy)`, replace `.btn-cta` cyan/pill styling with yellow/navy `var(--radius-control)`, and replace hard-coded cyan `#0891b2` accents with `var(--color-brand-navy)`. Set the body font to Instrument Sans via tokens (drop Inter/Outfit/Space Grotesk families). Remove `ThemeColorService` calls.

- [x] **Step 4: Strip per-page font includes**

Remove remaining `fonts.googleapis.com`/`fonts.gstatic.com` links from `@extends` pages (`dashboard`, `orders`, `profile`, `referral`, `reseller`, `support`, `topup`). Standalone pages (`storefront`, `spin-preview`) are out of Phase 0 scope and keep their own assets until Phase 3/4 — exclude them from the no-CDN assertion list.

- [x] **Step 5: Docs**

Create `docs/design-system.md` from the novagel handoff with these edits: brand name comes from `Brand`/settings; routes listed as Bundles/Support; note Phase 0 scope and legacy bridge. Add a README "Design system and frontend build" section with the Node path, `npm install`, `npm run build`, `npm run dev`, and the rule "every migrated page uses x-layouts.public/member".

- [x] **Step 6: Full verification**

Run:

```powershell
$env:PATH = 'C:\laragon\bin\nodejs\node-v22;' + $env:PATH
npm run build
php artisan test
php artisan route:list | Select-String 'home|bundles|design-system'
```

Also confirm the legacy alias utilities compiled:

```powershell
Select-String -Path public/build/assets/*.css -Pattern '\.bg-primary-600'
```

Expected: at least one match (proves the bridge palette is in the built CSS). Build succeeds; all tests PASS; route list shows unique names; `GET /design-system` renders.

- [x] **Step 7: Commit**

```bash
git add resources/views resources/js docs README.md tests
git commit -m "feat: unify legacy layout with compiled design system"
```

---

## Verification Checklist (end of Phase 0)

- [ ] `npm run build` exits 0; manifest lists both entries.
- [ ] `php artisan test` PASS (smoke, design system, brand, navigation, tokens, legacy layout).
- [ ] `/`, `/bundles`, `/login`, `/signup`, `/design-system`, `/dashboard` return 200 via `php artisan serve`.
- [ ] Migrated pages and `@extends` pages contain no `cdn.tailwindcss.com`, `unpkg.com`, or `fonts.googleapis.com`.
- [ ] Duplicate route-name test PASS.
- [ ] Manual responsive check at 360/768/1280 with keyboard focus pass.
- [ ] Working tree clean; all work committed on branch `feat/one-system-foundation` and pushed.

## Progress Log

- Phase 0 complete (Tasks 1-6): test harness, Vite/Tailwind/Alpine pipeline, design tokens/components, public + member shells (desktop sidebar, mobile bottom nav), pilot pages `/`, `/bundles`, `/login`, `/signup`, legacy palette bridge, docs.
- Phase 1 started: all member pages (`/dashboard`, `/orders`, `/topup`, `/referral`, `/reseller*`, `/profile`, `/notifications`) and public pages (`/support`, `/privacy`, `/terms`, `/maintenance`, `/forgot-password`) now render inside the unified shells; legacy cyan/Outfit styling bridged to brand tokens. Inner card polish continues.
- Shop auth + finance: shop checkout requires the r2b login, carts/orders carry user_id, login/register/refresh set the httpOnly uth_token cookie, member shop orders match user_id (with legacy phone/email fallback); /admin/statistics is the single finance dashboard with shop revenue, paid/pending shop orders, delivery fees, combined revenue and shop daily breakdown. MFA is out of scope by decision.
- Phase 2 storefront: `shop_*` schema + models, guest cart/checkout/Paystack/tracking, catalog seeder, shop pages under `/shop`, cart badge in header, unified member "Shop orders" tab (`/orders/shop` + `/api/auth/shop-orders`), admin catalog & shop orders (`/admin/shop`, `/api/admin/shop/*`).
- Evidence: `php artisan test` 34 passed / 285 assertions; `npm run build` clean; live checks `/shop`, `/shop/product/...`, `/orders/shop`, `/admin/shop` all 200.
- Branch pushed: `feat/one-system-foundation` (origin).

### Next

1. Phase 5: QA sweep already started (Playwright screenshots at 390/1280 for public, member, admin); deploy build to cPanel when credentials/environment are supplied.

### Completed follow-ups

- Phase 1 remainder: member cards/buttons normalised to tokens (cash accents, radii, shadows removed); desktop sidebar + mobile off-canvas sidebar (member, admin, public header) with bottom navigation retained everywhere; currency unified to GHS.
- Phase 2 remainder: product image uploads (storage + admin thumbnails), variant CRUD, delivery-zone/service-charge settings stored via SettingsService and used by checkout and validation.
- Phase 3 complete: reseller storefront and spin preview render inside the shared public shell; all view CDNs removed (Tailwind/Alpine/fonts/confetti now bundled); spin wheel uses brand palette, no gradients or glow.
- Phase 4 complete: one admin shell (sidebar + mobile drawer + bottom nav), all 14 admin views migrated, `/admin/*` gated server-side by `jwt.cookie` + `admin.web` (guest -> login, non-admin -> 403). MFA explicitly out of scope per user decision.
- QA: Playwright screenshots captured at 390px and 1280px for home, shop, bundles, store, design system, dashboard, orders, top-up, shop orders, admin dashboard, admin shop and admin statistics; mobile sidebars verified open/close.

### Shop policy update (latest user requirements)

- Orders and Shop orders are separate pages and separate sidebar entries; the data Orders page no longer mixes shop tabs.
- Delivery details are saved on the user (`delivery_*` columns), auto-filled at checkout, auto-updated after each order and editable from Profile.
- Shop no longer behaves as guest: cart, checkout and wallet payment require the r2b login (auth middleware redirects guests to login); guest wording removed.
- Cart is shop-only: header cart link/badge renders only inside `/shop*`; data flows never touch the cart.
- Top up before purchase: checkout is wallet-only. The order and wallet debit happen in one transaction; insufficient balance blocks the order with a Top up CTA (Paystack shop checkout removed).

### Unified sidebar and enforced login (latest)

- Business name default set to `DEV ESCCAY TEMPLATE` (SettingsService fallback, `.env`, `.env.example`).
- Shop pages now render inside the member shell, so data and shop share the same sidebar, topbar and bottom navigation; cart link/badge appears in the member topbar only on `/shop*`.
- Login is enforced server-side for the whole shop and member area (`web.auth` on `/shop/*`, dashboard, orders, shop orders, topup, referral, reseller, profile, notifications).
- `JwtFromCookie` is appended to the global web stack so the `auth_token` cookie authenticates every web request (browser + curl), with `auth_token` excluded from cookie encryption.

### Branding and navigation fixes

- New DEV ESCCAY TEMPLATE logo set: `public/assets/dev-esccay-mark.svg` (header/sidebar), `dev-esccay-favicon.svg` (favicon), `dev-esccay-logo.svg` (horizontal lockup for docs/deploy). SettingsService logo/favicon fallbacks now point to the new assets.
- Public mobile navigation drawer moved to the left and overlay strengthened so it matches the member/admin sidebars (sidebars are left-hand everywhere; login page has no sidebar).

### Public shop browsing

- Shop catalogue is public again: `/shop`, category, product, search, more and track render for guests in the public shell (header/footer/bottom nav), like data bundles.
- Purchase actions stay gated: cart add/view, checkout, wallet payment and shop orders require login (`web.auth`), and product pages show "Sign in to buy" for guests.
- Adaptive shell `x-layouts.shop`: guests get the public shell, logged-in users get the shared member sidebar, so both systems keep the same sidebar once signed in.

### Referral and reseller are data-only

- Sidebar group renamed to `Data earnings` with `Data referral` and `Data reseller` items.
- Referral and reseller pages state that rewards/tools apply to data bundle purchases only, not shop orders.
- Shop checkout creates only the shop order, wallet debit and `shop_payments` record; it never credits affiliate balances, reseller earnings or referral commissions. Regression test added (`shop purchase does not create reseller or referral earnings`).

### Auth state and palette contrast

- Header/drawer account state is server-rendered: guests see `Sign in` only; authenticated users see `Account` + `Sign out` and no `Sign in`. Authenticated visitors to `/login` or `/signup` are redirected to their dashboard (admin -> admin console).
- Logout now calls `POST /logout`, invalidates the JWT, clears the httpOnly `auth_token` cookie and local storage before returning to `/login`.
- Palette softened to reduce glare while keeping WCAG AA: navy #0A2038, gold #F0C232 (hover #D4A81F), red #DB4650 / #A92D38, surfaces #FCFBF8 / #F2F0EB. Network badge colours muted to match.

- Fixed home page auth mismatch: the New here? Create free account / Sign in banner now renders only for guests; authenticated users see Welcome back with Dashboard and Shop orders. Updated the stale Shopping is coming copy to reflect the live shop.

- Sidebar trigger moved to the far left of the public header (before the brand), matching member/admin topbars; brand wordmark collapses to the logo on very small screens so the row stays on one line.

- Home stays public outside login: authenticated users can view the landing page (no redirect); the CTA switches to Welcome back with Dashboard (admin-aware) and Shop. Login/signup still land on the dashboard after signing in.

- Fixed the refresh loop: /login and /signup no longer server-redirect authenticated visitors (dashboard's client guard sent them back, causing ping-pong). Authenticated login/signup pages now self-heal via POST /auth/session-token (new endpoint using the r2b cookie session), store the JWT in localStorage and redirect once; if minting fails the page stays put instead of looping.

### PATUPAA services integration (data, WAEC, utility, subscriptions, AFA)

- API client PatupaaClient (X-API-Key/X-API-Secret, envelope + error handling, idempotency headers) with config services.patupaa (PATUPAA_API_BASE_URL, PATUPAA_API_KEY, PATUPAA_API_SECRET).
- Catalog mirror patupaa_catalog_items + PatupaaCatalogSync: data bundles, airtime/digital products, WAEC products, utility services; items missing at PATUPAA are marked emote_enabled = false; admin manual override (dmin_enabled) and global per-service switches + AFA price are enforced together. Admin console: /admin/services with sync, switches, AFA price and per-item on/off.
- Wallet-paid service orders (service_orders) through PatupaaOrderService: debit before API submit, auto-refund and failed/refunded status when PATUPAA rejects or errors; order pages under /services/orders.
- Customer pages (public browsing, login to buy): /services hub, /services/data, /services/waec, /services/utility (with account lookup), /services/subscriptions (gift cards region/terms + shared accounts), /services/afa. Data navigation now points to the PATUPAA data channel; shop remains fully local.

### Accounting integrity and duplicate protection

- Duplicate prevention: shop checkout and every service purchase form now carry a request token stored as a unique idempotency_key (per user) on shop_orders / service_orders; replays redirect to the original order instead of charging again, and an atomic cache lock blocks concurrent submits. Request tokens are also sent to the provider as idempotency keys.
- Top-ups: manual approval now runs in a transaction with lockForUpdate on topup and user; second approval returns 400 and credits once. Paystack verification guards already-completed payments.
- Accounting: admin finance dashboard now includes service revenue, paid/refunded service orders and a service daily breakdown; combined revenue = data + shop + services. Regression tests cover replayed shop checkout, replayed service purchase, double topup approval, and finance totals.

### Template hardening (distribution-ready)

- Security: global security headers + HSTS over HTTPS, forced HTTPS and destructive-command ban in production, rate limits on login/registration/OTP, storefront webhook, shop checkout and service purchases.
- Secrets/seeders: .env.example now ships production-safe defaults (APP_ENV/DEBUG/timezone/session secure+encrypt); admin is seeded from SEED_ADMIN_* outside local; demo user/catalog only in local/testing or with SEED_DEMO_CATALOG=true. Hardcoded demo credentials removed from the seeder.
- Consistency: legacy /bundles redirects to the provider-backed data page and all public links point there; brand fallback derives from APP_NAME only; timezone Africa/Accra.
- Ops: /up health endpoint, README production checklist, config/route/view cache verified, providers registered via ootstrap/providers.php. Regression tests cover headers, health and timezone.

### Multi-source providers, feature toggles and five designs

- service_providers table with encrypted credentials, per-source services, default selection and last-sync status; catalog items and service orders carry provider_id; sync/order routing/lookup use the item's own source. Admin /admin/services manages sources (add/edit/delete/default/sync) with masked keys; default bootstrapped from env.
- Admin /admin/settings gained Features switches (module hide + 404 enforcement, nav filtering) and System design with five presets (CSS variable injection, WCAG AA tested). Feature/theme settings APIs under /api/admin/settings/*. Regressions: provider CRUD/sync/order routing/inactive source; feature toggle nav+routes; five presets contrast.

### Mall-only adaptive mode

- Feature toggles now adapt navigation: member sidebar gains Cart under Services and bottom navigation becomes Home / Super Mall / Cart / Profile when Mall is the only customer module (Bundles only when data is enabled). Shop orders + Wallet remain visible when enabled.
- /dashboard redirects to the Super Mall when only shop modules are on; public home hides data sections/buttons and shows the mall hero/cards; footer/hero links are feature-gated. Admin Settings > Features has one-click Mall only and Enable all presets.
- Regression tests: mall-only sidebar/bottom nav, home copy without data sections, dashboard redirect.

### Developer account and template licensing

- New developer role with 	emplate:dev-account artisan command (email/password/name/phone), env seeding via SEED_DEVELOPER_*, and access to both /developer and the admin console.
- 	emplate_licenses table + developer console: issue licenses (client, email, domain, agreement reference, expiry, notes) with hashed keys shown once, revoke/reinstate, and set the deployment license for the installation. Dashboard shows license/module/admin stats and quick links to settings, service sources and shop.
- Tests cover command seeding, access control, key issue/revoke/reinstate, expiry, deployment license validation and admin access.

### Developer-controlled setup, package capture and handover

- Service provider sources/credentials, service switches and AFA price are developer-only at the UI and API level; admins retain catalog toggles and business settings.
- 	emplate_packages stores encrypted snapshots of the current template setup (design, features, services, AFA price, provider sources). Developer console creates/applies packages without touching business data.
- Handover requires an active deployment license and typed confirmation; it invalidates the developer session, blocks developer login, /developer and developer admin access via middleware, while the client admin keeps full business access.
- Tests cover package capture/apply (including provider credential restore), handover gating, post-handover lockout and admin-only developer restrictions.

### Web template registry and GitHub delivery

- web_templates registry (local git path or git URL, branch, version) plus web_template_pushes audit. Developer console page /developer/templates adds/removes templates and pushes a template to a client GitHub repository (owner/repo/branch/private/note).
- GitHubService creates repositories via the API when missing and builds a token-authenticated remote; GitTemplatePusher clones the source into temp and pushes it, with tokens redacted from failures. Interface-bound pusher allows deterministic tests.
- Tests cover registry access, token-gated push, repository creation, success/failure records and an end-to-end git push to a bare remote.

### Five individual template products

- config/template_products.php defines five product lines: data+subscriptions+mall, data+subscriptions, mall only, subscription only, data only — each with its own feature map, provider-service switches and design preset.
- Developer → Template products (/developer/products) applies a product to the deployment in one click (features, services, AFA price, design) and records the active product; developer dashboard shows the five products for quick setup. Admin-only access is denied.
- Tests: five variants present, mall-only and data+subscription setups, admin denial.

### Product paths are isolated from the developer deployment

- Applying a product directly to the current deployment was removed: each template product now has its own console path (/developer/products/{key}) and an independent preview path (/developer/products/{key}/preview) with the product's theme and module setup.
- Creating a client package from a product snapshots its features/services/design plus current provider sources into 	emplate_packages without touching this deployment; packages are applied on client installations before licensing/handover.
- Developer products, dashboard, tests updated; global settings restored after local experimentation.

### Provider-style template catalog and licensing

- Modeled after standard template marketplaces: category/version, highlights, live preview, docs link, feature matrix, includes, support/update policy, changelog and per-product paths.
- Standard and Extended license tiers per product with prices, deployment limits, resale rights and support windows; issuing a license records product/tier/price/deployments/support end.
- Tests cover catalog structure, tier pricing and license field persistence; local migration applied.

### Service sources moved out of the admin business page

- The developer Setup link now opens /developer/services (service availability, AFA price, provider sources) inside the developer console; /admin/services is catalog-only for admins (provider sections hidden, provider APIs remain developer-only).
- Regression test asserts admins cannot open the developer service page and the admin services page never shows provider settings.

### Service management is developer-only; developer cannot manage the business

- Removed /admin/services entirely (404) and its admin navigation entry; service availability, AFA price, provider sources and catalog overrides now live only at /developer/services. All /api/admin/services* endpoints require a developer token.
- Developers no longer have access to the business admin console (/admin/*) or admin APIs; their console is limited to template products, packages, web templates, licenses, service sources and handover. Tests updated accordingly.

### Humanised AFA service page

- Replaced the generic eyebrow/heading/paragraph header with a practical service introduction: back to all services, AFA mark, direct title and useful facts (fee, completion, reference, refund).
- Added a subject-specific Ghana Card number example with helper copy, a before-you-start checklist, form helper text, numbered after-submit sequence and a short FAQ using native details elements.
- Kept the design system palette/components; screenshots at 1280 and 390 reviewed.
