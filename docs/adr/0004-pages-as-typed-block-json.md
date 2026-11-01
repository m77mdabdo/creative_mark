# ADR-0004: Pages as versioned typed-block JSON

- Status: Accepted (Gate 4, 2026-11-01)
- Decision: pages.draft_sections holds the working copy; each publish writes an immutable page_revisions row referenced by pages.published_revision_id. Each section type has a server-side schema; unknown types/fields, unsafe links and HTML are rejected.
- Rejected: One row per section; free HTML blocks.
- Consequences: Schemas are the contract between builder, renderer and server.

Full context: Phase E+F architecture document (governance/phase-ef-architecture-security.md).
