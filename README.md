# Datatem — One System

Combined Ghana-focused platform: mobile data bundles plus an online shop in one account, one checkout and one admin back office. The application is built on Laravel 11 (r2b base) with a uniform Tailwind 4 + Blade design system.

## Stack

- PHP 8.3, Laravel 11, MySQL 8 (SQLite for tests)
- Vite 8, Tailwind CSS 4, Alpine.js 3, Instrument Sans
- PHPUnit 10

## Local setup

```powershell
composer install
cp .env.example .env
php artisan key:generate

$env:PATH = 'C:\laragon\bin\nodejs\node-v22;' + $env:PATH
npm install
npm run build

php artisan migrate --seed
php artisan serve
```

Database settings come from `.env`. Tests use SQLite `:memory:` and never touch the configured MySQL database.

## Commands

```powershell
npm run build          # production assets
npm run dev            # Vite dev server
php artisan test       # PHPUnit suite
php artisan route:list # route inventory
```

## Structure

- `app/Http/Controllers` — API and page controllers (r2b data system)
- `app/Support` — `Brand` (settings-driven identity) and `Navigation` (public/member nav)
- `resources/views/components` — design-system components and shells
- `resources/views` — storefront and member pages
- `docs/design-system.md` — tokens, components, navigation model
- `docs/superpowers/plans` — implementation plans per phase

## Deployment

Shared cPanel hosting: deploy outside the web root, point the document root at `public/`, run `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `npm run build`, then cache config/routes/views. Never commit `.env`, `vendor/` or uploaded storage.
