import { Check } from 'lucide-react'
import TextBlock from '@/components/TextBlock'
import { HighlightedTitle } from './shared/SectionHeading'
import { headingMotion } from './shared/motionAttributes'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/**
 * A business solution's overview: the story with an optional gold note, beside a "what's included" panel.
 * The panel heading is the section subtitle; each card title is one included item.
 */
export default function ServiceOverview(props: SectionProps) {
  const { section } = props
  const items = section.cards.filter(c => c.title)

  return <SectionShell {...props}>
    <div className="wrap grid gap-12 lg:grid-cols-[1.15fr_1fr] lg:gap-20">
      <div {...headingMotion(section)}>
        {section.eyebrow && <p className="eyebrow mb-5 flex items-center gap-3"><span className="h-px w-8 bg-current" aria-hidden />{section.eyebrow}</p>}
        {section.title && <h2 id={`${section.id}-title`} className="font-display t-display-l"><HighlightedTitle title={section.title} highlight={section.highlight} /></h2>}
        <div className="gold-rule my-8 w-32" data-motion="line" aria-hidden />
        <div className="space-y-4"><TextBlock text={section.body} className="t-body-l muted measure" /></div>
        {section.note && <p className="accent mt-8 border-s-2 border-gold ps-5 text-lg font-medium measure">{section.note}</p>}
      </div>
      {items.length > 0 && <aside aria-labelledby={`${section.id}-scope`} className="self-start rounded-[8px] border border-gold/30 bg-gradient-to-b from-white/[.04] to-transparent p-7 md:p-9" data-motion="fade-up">
        {section.subtitle && <h3 id={`${section.id}-scope`} className="eyebrow mb-6">{section.subtitle}</h3>}
        <ul className="space-y-1">
          {items.map(item => <li key={item.id} className="flex min-h-11 items-start gap-3 border-b border-white/[.06] py-3 last:border-0">
            <span className="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-gold/15 text-gold" aria-hidden><Check size={14} /></span>
            <span>{item.title}{item.text && <span className="muted mt-1 block text-sm">{item.text}</span>}</span>
          </li>)}
        </ul>
      </aside>}
    </div>
  </SectionShell>
}
