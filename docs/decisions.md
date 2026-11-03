# Owner decisions after Gate 4

| ID | Date | Decision | Effect |
| --- | --- | --- | --- |
| D-07 | 2026-11-01 | The owner removes the public "translation under review" notice and accepts responsibility for the published translations ("شيله تحت المسؤولية بتاعتي"). | `settings.site.translation_notice = false`. Review statuses are still recorded per language in the dashboard; RULE-06's sitemap exclusion is to be revisited when the sitemap is built. |
| D-08 | 2026-11-01 | Hero becomes an auto-advancing slideshow of Saudi landmarks (owner request), replacing the "no auto-advancing carousel" rule for the hero only. | Pause control and reduced-motion static mode kept for WCAG 2.2.2. |
| D-09 | 2026-11-03 | The owner approves the eleven presentation figures (investment, municipal, industrial and other licence counts) and the three client reviews for publication ("اعملي رقم واحد موافق عليه"). | All statistics `verified = true` with the approval recorded as their source; testimonials `verified = true` with `consent_recorded_at` set (they stay anonymous). RULE-01/02 still apply to anything added later. |
| D-10 | 2026-11-03 | The owner approves the economic figures in the home page "Why invest" cards, asks for the latest news and case studies on the home page, and for a WhatsApp button with a temporary number until the real one is supplied. | Cards marked verified; news (3) and case studies (2) sections added before the call to action and published; WhatsApp `+966 50 000 0000` is a placeholder to replace in Settings › Contact details before launch. |
