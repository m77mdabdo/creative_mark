import Icon from '@/components/Icon'
import type { StatItem } from '@/types'
import { groupMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/**
 * "Numbers that build trust" (FR-015), drawn as the deck's dark stone blocks: the two headline figures large,
 * the other primary figures in a row of blocks, and secondary figures as a compact ledger.
 * Only verified figures are ever sent (RULE-01); the section is omitted when there are none.
 */
export default function TrackRecord(props: SectionProps) {
  const { section, page } = props
  const animate = section.motion.preset === 'counter'
  const primary = page.stats.filter(s => (s.group ?? 'primary') === 'primary')
  const secondary = page.stats.filter(s => s.group === 'secondary')
  const [lead, rest] = [primary.slice(0, 2), primary.slice(2)]

  return <SectionShell {...props}>
    <div className="wrap">
      <div className="mb-16"><SectionHeading section={section} center /></div>
      <dl className="mx-auto grid max-w-5xl grid-cols-2 items-end gap-4 md:gap-6" {...groupMotion(section)}>
        {lead.map((stat, i) => <StatBlock key={stat.key} stat={stat} animate={animate} tall={i === 0} />)}
      </dl>
      {rest.length > 0 && <dl className="mx-auto mt-4 grid max-w-5xl grid-cols-2 gap-4 md:mt-6 md:gap-6 lg:grid-cols-4" {...groupMotion(section)}>
        {rest.map(stat => <StatBlock key={stat.key} stat={stat} animate={animate} compact />)}
      </dl>}
      {secondary.length > 0 && <dl className="mx-auto mt-10 grid max-w-5xl grid-cols-1 border-t border-white/10 sm:grid-cols-2 lg:grid-cols-3" {...groupMotion(section)}>
        {secondary.map(stat => <div key={stat.key} className="flex items-center gap-4 border-b border-white/10 px-2 py-4">
          <Icon name={stat.icon} size={22} stroke={1.2} className="shrink-0 text-gold/80" />
          <dd className="font-display w-16 shrink-0 text-3xl leading-none text-gold [font-variant-numeric:lining-nums]"><StatValue value={stat.value} animate={animate} /></dd>
          <dt className="text-sm leading-snug text-white/75">{stat.label}</dt>
        </div>)}
      </dl>}
    </div>
  </SectionShell>
}

function StatBlock({ stat, animate, tall = false, compact = false }: { stat: StatItem; animate: boolean; tall?: boolean; compact?: boolean }) {
  return <div className={`relative flex flex-col items-center justify-end overflow-hidden rounded-[6px] border border-white/10 bg-gradient-to-b from-[#1d1c1a] via-[#121211] to-[#0a0a0a] px-4 text-center shadow-[inset_0_1px_0_rgba(255,255,255,.06),0_30px_60px_-30px_rgba(0,0,0,.9)] ${compact ? 'pb-6 pt-8' : `pb-8 ${tall ? 'pt-14 md:pt-20' : 'pt-10 md:pt-14'}`}`}>
    <span className="absolute inset-x-6 top-0 h-px bg-gradient-to-r from-transparent via-gold/70 to-transparent" aria-hidden />
    <Icon name={stat.icon} size={compact ? 28 : 40} stroke={1} className={`${compact ? 'mb-4' : 'mb-6'} text-gold`} />
    <dt className={`order-last mt-3 font-semibold text-white/80 ${compact ? 'text-xs' : 'text-xs uppercase tracking-[.16em]'}`}>{stat.label}</dt>
    <dd className={`font-display [font-variant-numeric:lining-nums] leading-none text-gold ${compact ? 'text-5xl' : 'text-6xl md:text-8xl'}`}><StatValue value={stat.value} animate={animate} /></dd>
  </div>
}

/** "1,000+" renders as text first (no JS, reduced motion); the counter preset animates the number. */
function StatValue({ value, animate }: { value: string; animate: boolean }) {
  const match = value.match(/^(\d[\d,]*)(\D*)$/)
  if (!match || !animate) return <span dir="ltr">{value}</span>
  return <span dir="ltr" data-counter={match[1].replace(/,/g, '')} data-suffix={match[2]}>{value}</span>
}
