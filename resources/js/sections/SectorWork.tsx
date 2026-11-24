import Icon from '@/components/Icon'
import Media from '@/components/Media'
import SmartLink from '@/components/SmartLink'
import TextBlock from '@/components/TextBlock'
import type { Card, MediaItem } from '@/types'
import { groupMotion, headingMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/**
 * "Sectors we worked in": a square card per sector, three per row on laptops and two on phones. Each card has the
 * sector's photograph (it zooms and a gold sheen crosses it on hover), its icon, name and description, and the
 * number of companies established in it when that figure is verified (RULE-01; the unit is the section's note).
 * Incomplete last rows are centred.
 */
export default function SectorWork(props: SectionProps) {
  const { section, page } = props
  const animate = section.motion.preset === 'counter'

  return <SectionShell {...props}>
    <div className="wrap">
      <div className="mb-12 grid items-end gap-8 md:mb-16 lg:grid-cols-[1.2fr_1fr] lg:gap-10">
        <SectionHeading section={section} />
        <div {...headingMotion(section)}><TextBlock text={section.body} className="muted measure leading-relaxed" /></div>
      </div>
      <ul className="flex flex-wrap justify-center gap-3 md:gap-5" {...groupMotion(section)}>
        {section.cards.map((card, i) => <li key={card.id} className="basis-[calc(50%-6px)] md:basis-[calc(50%-10px)] lg:basis-[calc(33.333%-14px)]">
          <SectorCard card={card} index={i} media={card.media ? page.media[card.media] : undefined} unit={section.note} animate={animate} />
        </li>)}
      </ul>
    </div>
  </SectionShell>
}

function SectorCard({ card, index, media, unit, animate }: { card: Card; index: number; media?: MediaItem; unit?: string | null; animate: boolean }) {
  const inner = <article tabIndex={card.href ? undefined : 0}
    className="group relative aspect-square overflow-hidden rounded-[18px] border border-white/10 bg-ink-2 shadow-[0_30px_60px_-34px_rgba(0,0,0,.95)] transition duration-500 ease-[var(--ease-out)] hover:-translate-y-1.5 hover:border-gold/60 hover:shadow-[0_34px_70px_-30px_rgba(235,189,108,.35)] focus-within:border-gold/60 md:aspect-[4/4.2]">
    <Media media={media} sizes="(min-width: 1024px) 33vw, 50vw" decorative
      className="absolute inset-0 h-full w-full transition duration-[1.2s] ease-[var(--ease-out)] group-hover:scale-[1.09]" />
    <div className="absolute inset-0 bg-gradient-to-t from-black/95 via-black/45 to-black/10" aria-hidden />
    <div className="sector-sheen pointer-events-none absolute inset-0" aria-hidden />

    <div className="absolute inset-x-0 top-0 flex items-center justify-between p-3 md:p-6">
      <span className="grid h-9 w-9 place-items-center rounded-full border border-gold/50 bg-black/40 text-gold backdrop-blur-sm transition duration-500 group-hover:rotate-[8deg] group-hover:bg-gold group-hover:text-ink md:h-12 md:w-12">
        <Icon name={card.icon} size={18} stroke={1.4} />
      </span>
      <span className="font-display text-base text-white/40 md:text-xl" aria-hidden dir="ltr">{String(index + 1).padStart(2, '0')}</span>
    </div>

    <div className="absolute inset-x-0 bottom-0 p-3 text-white md:p-6">
      {card.value && <p className="mb-1.5 flex flex-wrap items-baseline gap-x-2 md:mb-3">
        <span className="font-display text-3xl leading-none text-gold [font-variant-numeric:lining-nums] md:text-6xl"><Value value={card.value} animate={animate} /></span>
        {unit && <span className="text-[11px] font-semibold text-white/75 md:text-sm">{unit}</span>}
      </p>}
      <h3 className="text-[15px] font-semibold leading-snug md:text-2xl">{card.title}</h3>
      {card.text && <p className="mt-2 hidden text-sm leading-relaxed text-white/75 md:block">{card.text}</p>}
      <span className="mt-3 block h-px w-10 bg-gold transition-all duration-500 group-hover:w-24 md:mt-5" aria-hidden />
    </div>
  </article>

  return card.href ? <SmartLink href={card.href} className="block rounded-[18px]">{inner}</SmartLink> : inner
}

function Value({ value, animate }: { value: string; animate: boolean }) {
  const match = value.match(/^(\d[\d,]*)(\D*)$/)
  if (!match || !animate) return <span dir="ltr">{value}</span>
  return <span dir="ltr" data-counter={match[1].replace(/,/g, '')} data-suffix={match[2]}>{value}</span>
}
