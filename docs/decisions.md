# Owner decisions after Gate 4

| ID | Date | Decision | Effect |
| --- | --- | --- | --- |
| D-07 | 2026-11-01 | The owner removes the public "translation under review" notice and accepts responsibility for the published translations ("شيله تحت المسؤولية بتاعتي"). | `settings.site.translation_notice = false`. Review statuses are still recorded per language in the dashboard; RULE-06's sitemap exclusion is to be revisited when the sitemap is built. |
| D-08 | 2026-11-01 | Hero becomes an auto-advancing slideshow of Saudi landmarks (owner request), replacing the "no auto-advancing carousel" rule for the hero only. | Pause control and reduced-motion static mode kept for WCAG 2.2.2. |
| D-09 | 2026-11-03 | The owner approves the eleven presentation figures (investment, municipal, industrial and other licence counts) and the three client reviews for publication ("اعملي رقم واحد موافق عليه"). | All statistics `verified = true` with the approval recorded as their source; testimonials `verified = true` with `consent_recorded_at` set (they stay anonymous). RULE-01/02 still apply to anything added later. |
