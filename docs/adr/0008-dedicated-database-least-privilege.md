# ADR-0008: Dedicated database and least-privilege users on XAMPP

- Status: Accepted (Gate 4, 2026-11-01)
- Decision: Database creative_mark (utf8mb4); cm_migrate (DDL on creative_mark only) and cm_app (DML only) bound to localhost. Root never used by the app.
- Rejected: Shared root account.
- Consequences: Two credential sets in .env; production runtime user loses UPDATE/DELETE on audit_logs.

Full context: Phase E+F architecture document (governance/phase-ef-architecture-security.md).
