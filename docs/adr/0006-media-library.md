# ADR-0006: Spatie Media Library with private disk for lead files

- Status: Accepted (Gate 4, 2026-11-01)
- Decision: MediaAsset model with public_media disk and queued AVIF/WebP conversions; private disk for lead attachments served via policy + signed URL; no SVG uploads.
- Rejected: Plain storage links.
- Consequences: A queue worker must run for conversions.

Full context: Phase E+F architecture document (governance/phase-ef-architecture-security.md).
