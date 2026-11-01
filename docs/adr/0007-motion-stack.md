# ADR-0007: GSAP + ScrollTrigger + Lenis tied to Inertia navigation

- Status: Accepted (Gate 4, 2026-11-01)
- Decision: Presets live in code; the CMS stores {preset, intensity, delay}. Instances are killed on Inertia navigate. prefers-reduced-motion disables Lenis, parallax, zoom and autoplay.
- Rejected: CSS-only motion; Framer Motion.
- Consequences: Animation code lazy-loaded to respect the 180 KB JS budget.

Full context: Phase E+F architecture document (governance/phase-ef-architecture-security.md).
