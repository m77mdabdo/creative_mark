# Creative Mark — development guide

Laravel 13 monolith: public site in React + TypeScript via Inertia, admin in Filament (next milestone).
Governance docs (requirements, UX, architecture, ADRs) are the source of truth: `docs/`.

## Environment (owner's Mac, shared with other XAMPP projects)

- PHP: Homebrew 8.5.2 on PATH (`php`). Serve with `php artisan serve` (http://127.0.0.1:8000).
  Never use XAMPP's PHP 8.1 or Apache for this project. Never install or change a global PHP.
- Database: XAMPP MariaDB 10.4 via socket `/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock`,
  database `creative_mark`. Runtime user `cm_app`, migrations as `cm_migrate` (see `.env.example`).
  Never put `root` in `.env`. Never touch other databases.
- Do not edit anything under `/Applications/XAMPP/xamppfiles` except this folder, and never other `htdocs` projects.
- Dependencies are project-local only (`vendor/`, `node_modules/`); no `composer global` / `npm -g`.
- Do not run remote installer scripts piped to a shell.

## Commands

- `composer test` — PHP feature tests
- `npm run build` / `npm run typecheck`
- `php artisan migrate --database=mariadb_migrate` — migrations run as the migrate user
- `docker compose -f infra/translation/docker-compose.yml up -d` — local LibreTranslate (machine translation)
- `php artisan cm:translate [--locale=fr] [--refresh] [--publish]` — fill missing languages (marked "machine")

## Content rules (from approved requirements)

- RULE-01: figures render only when `verified`. RULE-02: testimonials show names only with consent.
- RULE-03: no demo/placeholder content published. RULE-04: stock photos are placeholders, never captioned as staff/clients.
- RULE-09: Vision 2030 / Invest Saudi as text only; no official logos without permission.
- Pages are typed-block JSON validated on the server; never render CMS text as HTML.
- Never invent statistics, testimonials or legal claims. Never claim tests pass without output.

## Known local issue: Composer crashes (exit 139)

Homebrew's libcurl 8.22 segfaults in its HTTP/3 (ngtcp2) path, so `composer require/update` dies silently.
Workaround without touching the global install: copy `/opt/homebrew/Cellar/composer/*/bin/composer` to a scratch
`cm.phar`, change `if (!$willUseProxy && \defined('CURL_VERSION_HTTP3')` to `if (false && ...)` in
`src/Composer/Util/Http/CurlDownloader.php` (with `php -d phar.readonly=0`), and run `php cm.phar ...`.

## Code map

Backend (`app/`)
- `Support/Locales.php` — the six locales, tags, direction, Accept-Language matching.
- `Http/Middleware/SetLocale.php` — `/{locale}` prefix; `HandleInertiaRequests.php` — shared props (locale, nav, contact).
- `Content/SectionSchema.php` — server contract for page blocks (types, fields, safe links, no HTML).
- `Content/PublishPage.php` — draft → immutable revision → live.
- `Content/HomePageData.php` — builds Home props; every content rule (RULE-01/02/06) is applied here.
- `Models/*` + `Models/Concerns/HasTranslations.php` — one `*_translations` row per locale, English fallback.

Frontend (`resources/js/`)
- `pages/` — Inertia pages. `layouts/SiteLayout.tsx` + `layouts/site/*` — header, mega menu, drawer, footer.
- `sections/` — one file per section type; `sections/registry.ts` maps CMS types to components;
  `sections/shared/` — shell, heading, card grid, motion attributes.
- `components/` — small reusable pieces (Media, Icon, SmartLink, ButtonGroup, Flag…).
- `motion/presets.ts` — all animation presets; `useMotion` runs them, `useSmoothScroll` adds Lenis.
- `types/` — props contract with the PHP side; `i18n/uiStrings.ts` — interface labels.

Translation (`app/Translation/`): `Translator` (driver fallback, glossary, phrasebook, cache) over
`Drivers/LibreTranslateDriver` (self-hosted, primary) and `Drivers/MyMemoryDriver` (free API, short labels, daily quota);
`ContentTranslator` fills records, page blocks, settings and `lang/ui/*.json`. Human-reviewed text is never overwritten.
Professional translations of every English master string live in `database/seeders/data/translation-memory/{locale}.json`
and always win over machine output; when English copy changes, add the new string there for fr, hi, ur and zh.

Adding a section type: PHP `SectionSchema::TYPES`, a component in `sections/`, an entry in `sections/registry.ts`, a test.

## Git workflow

- Remote: `origin` → github.com/m77mdabdo/creative_mark, branch `main`.
- Every change: add an entry to the README "Changelog", commit with a clear message, `git push origin main`.
- Commit messages describe the change only; no tool or attribution trailers.
