# ADR-0003: Per-locale translation tables with review status

- Status: Accepted (Gate 4, 2026-11-01)
- Decision: Each translatable model has a *_translations table (one row per locale) with review_status missing/machine/review/approved. Page section text is the exception (ADR-0004).
- Rejected: spatie/laravel-translatable JSON columns.
- Consequences: More tables and joins; eager-load current locale plus en fallback.

Full context: Phase E+F architecture document (governance/phase-ef-architecture-security.md).
