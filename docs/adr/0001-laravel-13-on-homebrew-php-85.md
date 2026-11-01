# ADR-0001: Laravel 13 on Homebrew PHP 8.5

- Status: Accepted (Gate 4, 2026-11-01)
- Decision: Keep Laravel 13.39 (owner D-01). Run on Homebrew PHP 8.5.2; XAMPP provides only the database server.
- Rejected: Downgrade to Laravel 12; upgrade XAMPP PHP.
- Consequences: Every package must support Laravel 13 / PHP 8.5; fallback is a project-local php@8.4.

Full context: Phase E+F architecture document (governance/phase-ef-architecture-security.md).
