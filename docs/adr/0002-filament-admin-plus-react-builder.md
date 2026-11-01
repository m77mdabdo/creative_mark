# ADR-0002: Filament 5 admin + one Inertia React builder screen

- Status: Accepted (Gate 4, 2026-11-01)
- Decision: Filament 5 panel at /admin for all modules. The visual builder is one Inertia React page at /admin/builder/{page} in the same web guard, auth, MFA and policies.
- Rejected: All-React admin; Livewire builder.
- Consequences: Two admin UI stacks share tokens and one policy layer.

Full context: Phase E+F architecture document (governance/phase-ef-architecture-security.md).
