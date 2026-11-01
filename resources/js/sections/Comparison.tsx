import { Check, Minus } from 'lucide-react'
import { groupMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/**
 * Two columns compared line by line: the first card is the "without" side, the second the "with" side.
 * Each card's text holds one item per line.
 */
export default function Comparison(props: SectionProps) {
  const { section } = props
  const [before, after] = section.cards
  if (!before || !after) return null
  const lines = (text: string | null) => (text ?? '').split('\n').map(l => l.trim()).filter(Boolean)

  return <SectionShell {...props}>
    <div className="wrap">
      <SectionHeading section={section} className="mb-14" />
      <div className="grid gap-5 lg:grid-cols-2 lg:gap-6" {...groupMotion(section)}>
        <div className="rounded-[8px] border border-white/10 bg-white/[.02] p-7 md:p-10">
          <h3 className="mb-6 text-sm font-semibold uppercase tracking-[.16em] text-white/60">{before.title}</h3>
          <ul className="space-y-1">
            {lines(before.text).map(item => <li key={item} className="flex items-start gap-3 border-b border-white/[.06] py-4 text-white/65 last:border-0">
              <span className="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full border border-white/20" aria-hidden><Minus size={12} /></span>{item}
            </li>)}
          </ul>
        </div>
        <div className="relative rounded-[8px] border border-gold/50 bg-gradient-to-b from-gold/[.08] to-transparent p-7 shadow-[0_40px_100px_-50px_rgba(235,189,108,.45)] md:p-10">
          <h3 className="mb-6 text-sm font-semibold uppercase tracking-[.16em] text-gold">{after.title}</h3>
          <ul className="space-y-1">
            {lines(after.text).map(item => <li key={item} className="flex items-start gap-3 border-b border-gold/15 py-4 last:border-0">
              <span className="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-gold text-ink" aria-hidden><Check size={13} strokeWidth={2.5} /></span>{item}
            </li>)}
          </ul>
        </div>
      </div>
    </div>
  </SectionShell>
}
