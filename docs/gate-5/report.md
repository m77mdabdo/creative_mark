# Gate 5 — implementation report (milestone 1: foundation + homepage)

Nov 1, 2026 · Commits `7d9aae3`..HEAD on local `main` (no remote yet, A-03)

## الملخص (للمالك)

الصفحة الرئيسية تعمل بالعربية والإنجليزية من قاعدة البيانات، باتجاه «مجلس الليل» والذهبي #EBBD6C، مع الخريطة التفاعلية للمناطق الـ 13 والحركة واحترام تقليل الحركة. 29 اختباراً آلياً ناجحاً. **المتبقي الوحيد في هذه المرحلة هو قاعدة `creative_mark` على MariaDB، وهو ينتظر قرارك**؛ حتى ذلك الحين تعمل المعاينة المحلية على قاعدة SQLite مؤقتة خارج المشروع.

## Status per task

| ID | Status | Evidence |
| --- | --- | --- |
| G5-01 Environment safety | Done | Skeleton bootstrap docs replaced by `docs/DEVELOPMENT.md` (no installer pipe), `docs/adr/0001..0008` |
| G5-02 Dedicated DB | **Blocked on owner** (A-02) | Script ready: `database/setup/001_create_creative_mark.sql`; `.env.example` uses `cm_app`/`cm_migrate`; `.env` still points to root and is untouched |
| G5-03 Dependencies | Done | inertia-laravel 3.6, @inertiajs/react 3.10, React 19.3, TypeScript (strict), GSAP 3.15, Lenis 1.3, Vite 8, Tailwind 4.3. A-01: `composer require filament/filament:^5 spatie/laravel-permission spatie/laravel-medialibrary --dry-run` resolves Filament 5.11.0, Livewire 4.4.8, permission 8.3.2, medialibrary 11.23.11 on PHP 8.5.2 |
| G5-04 Locale routing | Done | `/` → 302 by cookie → Accept-Language → `ar`; 6 prefixes; `lang`/`dir` per locale; unknown → 404 |
| G5-05 DB-backed content | Done (on SQLite until G5-02) | 17 tables; home = published `page_revisions` row; schema validation on publish |
| G5-06 Shell + tokens | Done | Header (mega panel, drawer with focus trap/Esc/inert), language switcher, footer with both offices and 3 social links, skip link |
| G5-07 Homepage | Done | 11 sections in the approved order |
| G5-08 Motion | Done | 7 presets in `resources/js/motion/useMotion.ts`; Lenis desktop-only; reduced motion = static |
| G5-09 Verification | Done except MariaDB run | See below |

## Verification run (2026-11-01)

- `php artisan test`: **29 passed, 0 failed** (SQLite in-memory). Covers locale redirect/404/dir, approved section order, draft vs published, hidden sections absent from HTML, RULE-01 (only `1,000+` and `8` figures reach the HTML), RULE-02 (names only with consent), FAQ review filter, shared props follow the URL locale, and 13 rejected injection/validation cases for page blocks.
- `npx tsc --noEmit`: clean. `npm run build`: initial JS **174.3 KB gzip** (budget 180 KB); map data split into a 16.6 KB chunk loaded on demand.
- Playwright (Chromium, reduced motion) at 360×640, 390×844, 1440×900 in ar and en: horizontal overflow 0 px, one `h1`, hero CTA inside the first viewport (bottom at 442 px of 640 on the smallest), **0 console errors**. Map: 13 regions, keyboard Enter selects a region and the live region announces it.
- Two defects found during QA and fixed: shared props were resolved before the locale middleware (Arabic page showed English chrome), and custom `.btn` styles overrode Tailwind's responsive `hidden`.

## Content decisions applied (please review)

- **Arabic text is marked "machine / awaiting review"** because it was drafted in the prototype, not by a translator (D-05). The Arabic page therefore shows a small gold notice bar. Approving Arabic in the CMS (next milestone) removes it.
- **3 of 5 "Why invest" cards are hidden** (GDP SAR 4.8T, FDI +64.7%, private sector 65%) and the Business Consulting card shows its tagline instead of a summary quoting SAR 4.8T: these figures are unverified (RULE-01). Confirming the sources restores them.
- Track record shows only the owner-confirmed figures: 1,000+ companies, 8 years.
- Testimonials appear as "Client review" without names (RULE-02).
- Hero eyebrow "Vision 2030 · Invest Saudi" as text only; no official logos (RULE-09).
- Phone, email, WhatsApp and hours are not shown because they have not been supplied (G-01). LinkedIn URL is derived from the company ID and still needs confirmation.
- Photos are the prototype's Unsplash placeholders, converted to WebP 800/1600 px (RULE-04).

## Not in this milestone (next)

Filament admin, login, roles and MFA; consultation form and leads; the visual builder; other pages (all header links except Home return 404 for now); SSR; ui_strings table; navigation as its own table (currently a `settings` row).

## Known issue

Composer on this Mac segfaults (Homebrew libcurl HTTP/3). Worked around with a patched copy outside the project; global Composer unchanged. Details in `docs/DEVELOPMENT.md`.
