# ADR-0005: /{locale}/ URLs, Arabic default

- Status: Accepted (Gate 4, 2026-11-01)
- Decision: Prefixes ar en fr hi ur zh (tags ar en fr hi ur-PK zh-Hans). / redirects 302 by saved preference, then Accept-Language, then ar. dir=rtl for ar and ur. Admin URLs not prefixed.
- Rejected: Unprefixed default locale.
- Consequences: Every public route and sitemap entry is locale-aware; unreviewed locales show a notice.

Full context: Phase E+F architecture document (governance/phase-ef-architecture-security.md).
