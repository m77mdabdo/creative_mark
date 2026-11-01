import Icon from '@/components/Icon'
import type { StatItem } from '@/types'
import { groupMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/**
 * "Numbers that build trust" (FR-015), drawn as the deck's dark stone blocks.
 * Only verified figures are ever sent (RULE-01); the section is omitted when there are none.
 */
export default function TrackRecord(props: SectionProps) {
  const { section, page } = props
  const animate = section.motion.preset === 'counter'

  return <SectionShell {...props}>
    <div className="wrap">
      <div className="mb-16"><SectionHeading section={section} center /></div>
      <dl className="mx-auto grid max-w-4xl grid-cols-2 items-end gap-4 md:gap-6" {...groupMotion(section)}>
        {page.stats.map((stat, i) => <StatBlock key={stat.key} stat={stat} animate={animate} tall={i === 0} />)}
      </dl>
    </div>
  </SectionShell>
}

function StatBlock({ stat, animate, tall }: { stat: StatItem; animate: boolean; tall: boolean }) {
  return <div className={`relative flex flex-col items-center justify-end overflow-hidden rounded-[6px] border border-white/10 bg-gradient-to-b from-[#1d1c1a] via-[#121211] to-[#0a0a0a] px-4 pb-8 text-center shadow-[inset_0_1px_0_rgba(255,255,255,.06),0_30px_60px_-30px_rgba(0,0,0,.9)] ${tall ? 'pt-14 md:pt-20' : 'pt-10 md:pt-14'}`}>
    <span className="absolute inset-x-6 top-0 h-px bg-gradient-to-r from-transparent via-gold/70 to-transparent" aria-hidden />
    <Icon name={stat.icon} size={40} stroke={1} className="mb-6 text-gold" />
    <dt className="order-last mt-4 text-xs font-semibold uppercase tracking-[.16em] text-white/80">{stat.label}</dt>
    <dd className="font-display text-6xl [font-variant-numeric:lining-nums] leading-none text-gold md:text-8xl"><StatValue value={stat.value} animate={animate} /></dd>
  </div>
}

/** "1,000+" renders as text first (no JS, reduced motion); the counter preset animates the number. */
function StatValue({ value, animate }: { value: string; animate: boolean }) {
  const match = value.match(/^(\d[\d,]*)(\D*)$/)
  if (!match || !animate) return <span dir="ltr">{value}</span>
  return <span dir="ltr" data-counter={match[1].replace(/,/g, '')} data-suffix={match[2]}>{value}</span>
}
