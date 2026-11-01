# Creative Mark — Gate 5 execution plan (first build milestone)

Nov 1, 2026 · Prepared for mhmd abdo · Status: **awaiting owner approval**

## الملخص (للمالك)

هذه أول مرحلة كود فعلي. الهدف: تطبيق Laravel 13 يعمل محلياً على `http://127.0.0.1:8000` بقاعدة بيانات `creative_mark` مستقلة، وصفحة رئيسية عربية/إنجليزية بالاتجاه A «مجلس الليل» وبالذهبي #EBBD6C، ومحتواها مخزّن في قاعدة البيانات وليس مكتوباً في الكود.

**خارج هذه المرحلة (المرحلة التالية M1/M3):** لوحة Filament وتسجيل الدخول والأدوار، نموذج الاستشارة وحفظ العملاء المحتملين، الباني المرئي، SSR، باقي الصفحات واللغات الأربع الأخرى (تظهر بإشعار «الترجمة قيد المراجعة» وبمحتوى إنجليزي احتياطي).

**المطلوب منك:** (1) الموافقة على الخطة، (2) اختيار طريقة إنشاء قاعدة البيانات (G5-02).

## Baseline already verified (2026-11-01)

| Check | Result |
| --- | --- |
| `php -v` | PHP 8.5.2 (Homebrew, `/opt/homebrew/bin/php`) |
| `php artisan --version` | Laravel Framework 13.39.0 |
| `composer -V` | 2.9.5 |
| Node / npm | v24.22.2 / 11.19.1 |
| DB server | XAMPP MariaDB 10.4.28, socket `/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock` |
| Git | Initialised; commit `7d9aae3` = untouched skeleton (D-06) |
| `.env` now | `DB_USERNAME=root`, `DB_DATABASE=creativemark_app` → violates ADR-0008; replaced in G5-02 |

## Tasks

| ID | Pri | Objective | Files / modules | Acceptance criteria | Tests / evidence | Depends |
| --- | --- | --- | --- | --- | --- | --- |
| G5-01 | P0 | Environment safety | `docs/DEVELOPMENT.md`, `docs/adr/0001..0008.md` | Skeleton bootstrap text (pipes an installer to bash) removed; project rules written; ADRs committed | `git log` | — |
| G5-02 | P0 | Dedicated DB + least-privilege users | `database/setup/001_create_creative_mark.sql`, `.env`, `.env.example` | DB `creative_mark` utf8mb4; `cm_migrate` (DDL on that DB only), `cm_app` (SELECT/INSERT/UPDATE/DELETE only); no root in `.env`; passwords random, only in git-ignored `.env` | `SHOW GRANTS` output for both users; `php artisan migrate` as `cm_migrate` | Owner choice A-02 |
| G5-03 | P0 | Dependency install + compatibility | `composer.json`, `package.json` | `inertiajs/inertia-laravel`, `@inertiajs/react`, React 19 + TS strict, GSAP, Lenis installed with no platform overrides; Filament 5 + spatie packages checked with `composer require --dry-run` (A-01) | `composer show`, `npm ls`, `tsc --noEmit` clean | — |
| G5-04 | P0 | Locale routing (ADR-0005) | `SetLocale` middleware, `routes/web.php`, `config/locales.php` | `/` → 302 `/ar` (cookie, then `Accept-Language`, then `ar`); `/{ar,en,fr,hi,ur,zh}`; `html lang/dir` correct (rtl for ar, ur); unknown locale 404; fr/hi/ur/zh show "translation under review" notice | Feature tests | G5-03 |
| G5-05 | P0 | DB-backed home content (ADR-0003/0004) | Migrations + models: `pages`, `page_translations`, `page_revisions`, `services`(+tr), `regions`(+tr), `stats`(+tr), `countries`(+tr), `faqs`(+tr), `testimonials`(+tr), `settings`; seeders from prototype `seed.ts` (ar + en) | Home renders from the published revision; hidden sections absent from HTML (FR-021); unverified stats never sent to the browser (RULE-01); testimonials without consent show "Client review", no names (RULE-02); stat "1,000+ companies / 8 years" seeded verified (owner, 2026-11-01); other figures unverified | Feature tests on props and HTML | G5-02 |
| G5-06 | P0 | Site shell, direction A tokens | `resources/css/app.css`, `resources/js/layouts/SiteLayout.tsx`, header, mega panel, mobile drawer, footer | Tokens per Phase C/D §11 with gold `#EBBD6C` (button hover `#F3D08F`; gold never used as text on ivory/white, `--gold-text #7A5C2C` there); self-hosted Manrope, Cormorant Garamond, IBM Plex Sans Arabic; skip link; drawer traps focus + Esc; footer with Riyadh and Cairo offices and social links from `settings` | Playwright keyboard check | G5-03 |
| G5-07 | P0 | Homepage, 11 sections | `resources/js/sections/*.tsx` ported from prototype `Sections.tsx` | Order of Phase C/D §5.1: hero, why invest, journey, countries, sectors, track record, services (11), map (13 regions, keyboard + list alternative), FAQ preview (accessible accordion), CTA, testimonials; hero CTA inside first viewport at 360×640; no horizontal scroll at 320 px | Playwright screenshots ar+en at 390 and 1440 | G5-05, G5-06 |
| G5-08 | P1 | Motion system (ADR-0007) | `resources/js/motion/*` | `useMotion` with presets fade-up, reveal-mask, zoom-in, parallax-slow, stagger-cards, counter, line-draw; Lenis on desktop pointer only; all killed on Inertia navigate; `prefers-reduced-motion` disables parallax/zoom/Lenis and shows final counter values | Reduced-motion Playwright run | G5-07 |
| G5-09 | P0 | Verification and report | `tests/Feature/*`, `tests/e2e/*` | `php artisan test` green; `npm run build` green; JS budget measured against 180 KB (NFR-01, reported, not hidden); screenshots attached | Command output in report | all |

## Rules applied

- No edits to XAMPP config, Homebrew PHP or other `htdocs` projects; all packages project-local.
- No invented figures, testimonials or names; photos remain flagged placeholders (RULE-04).
- Vision 2030 as text only; no official logos (RULE-09).
- Commits per task on `main` locally; no remote exists yet (A-03).

## Decision needed: G5-02 database creation

The users can only be created by an account with `GRANT` rights (XAMPP `root`).

- **Option 1 (recommended):** I run the reviewed script once with the XAMPP `mysql` client as root, then never use root again. The script only creates `creative_mark`, `cm_migrate` and `cm_app`; it does not touch other databases or root.
- **Option 2:** You run the script yourself in phpMyAdmin (SQL tab) and tell me when it is done.

Also confirm that the existing `creativemark_app` database named in the current `.env` is not needed by this project (I will not drop it; it is simply no longer referenced).
