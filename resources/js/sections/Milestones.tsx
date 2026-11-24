import { useEffect, useRef } from 'react'
import Icon from '@/components/Icon'
import { gsap, prefersReducedMotion } from '@/motion/gsap'
import type { StatItem } from '@/types'
import { groupMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/** The figure shown large beside the timeline: years of experience when it is published. */
const LEAD_KEY = 'years-experience'

/**
 * "Our years and achievements". The years of experience count up in large gold numerals and stay in view (sticky on
 * wide screens) while the achievements from Content › Statistics arrive one by one along a gold line that fills as
 * the visitor scrolls. Confirmed years (the section's cards: title = year, text = what happened) follow as a
 * horizontal timeline. Every figure is a verified statistic (RULE-01); with reduced motion everything is static.
 */
export default function Milestones(props: SectionProps) {
  const { section, page } = props
  const animate = section.motion.preset === 'counter'
  const lead = page.stats.find(s => s.key === LEAD_KEY)
  const rest = page.stats.filter(s => s !== lead)
  const line = useRef<HTMLSpanElement>(null)
  const list = useRef<HTMLOListElement>(null)

  useEffect(() => {
    if (!line.current || !list.current || prefersReducedMotion() || section.motion.preset === 'none') return
    const ctx = gsap.context(() => {
      gsap.fromTo(line.current, { scaleY: 0 }, {
        scaleY: 1, ease: 'none', transformOrigin: 'top center',
        scrollTrigger: { trigger: list.current, start: 'top 70%', end: 'bottom 60%', scrub: 0.6 },
      })
    })
    return () => ctx.revert()
  }, [section.motion.preset])

  // Clipped rather than hidden overflow, so the years column can stay in view (sticky) beside the timeline.
  return <SectionShell {...props} className="overflow-clip!">
    <div className="wrap">
      <div className="grid gap-14 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-20">
        <div className="lg:sticky lg:top-[calc(var(--cm-header)+var(--cm-announce)+48px)] lg:self-start">
          <SectionHeading section={section} />
          {lead && <div className="relative mt-12 inline-flex items-end gap-5">
            <span className="pointer-events-none absolute -inset-10 rounded-full bg-[radial-gradient(circle,rgba(235,189,108,.22),transparent_65%)]" aria-hidden />
            <span className="font-display relative bg-gradient-to-b from-gold-soft via-gold to-gold-deep bg-clip-text text-[120px] leading-[.8] text-transparent [font-variant-numeric:lining-nums] md:text-[180px]">
              <Value value={lead.value} animate={animate} />
            </span>
            <span className="relative mb-3 max-w-[9rem] text-sm font-semibold uppercase leading-snug tracking-[.14em] text-white/80">{lead.label}</span>
          </div>}
        </div>

        <div className="relative">
          <span className="absolute inset-y-0 start-[19px] w-px bg-white/10 md:start-[23px]" aria-hidden />
          <span ref={line} className="absolute inset-y-0 start-[19px] w-px origin-top bg-gradient-to-b from-gold via-gold to-gold/30 md:start-[23px]" aria-hidden />
          <ol ref={list} className="relative space-y-4 md:space-y-5" {...groupMotion(section)}>
            {rest.map(stat => <Achievement key={stat.key} stat={stat} animate={animate} />)}
          </ol>
        </div>
      </div>

      {section.cards.length > 0 && <div className="mt-24">
        <ol className="relative grid gap-8 sm:grid-cols-2 lg:grid-flow-col lg:auto-cols-fr lg:grid-cols-none" {...groupMotion(section)}>
          {section.cards.map(card => <li key={card.id} className="relative border-t border-gold/40 pt-8">
            <span className="absolute -top-[7px] start-0 h-3.5 w-3.5 rounded-full border-2 border-gold bg-ink shadow-[0_0_0_6px_rgba(235,189,108,.12)]" aria-hidden />
            <p className="font-display text-5xl leading-none text-gold [font-variant-numeric:lining-nums]" dir="ltr">{card.title}</p>
            {card.text && <p className="mt-4 text-sm leading-relaxed text-white/75">{card.text}</p>}
          </li>)}
        </ol>
      </div>}
    </div>
  </SectionShell>
}

function Achievement({ stat, animate }: { stat: StatItem; animate: boolean }) {
  return <li className="group relative flex items-center gap-5 ps-14 md:gap-6 md:ps-16">
    <span className="absolute start-0 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full border border-gold/50 bg-ink text-gold shadow-[0_0_0_6px_rgba(11,11,12,1)] transition duration-500 group-hover:scale-110 group-hover:bg-gold group-hover:text-ink md:h-12 md:w-12">
      <Icon name={stat.icon} size={18} stroke={1.4} />
    </span>
    <div className="flex min-w-0 flex-1 items-center gap-5 rounded-[14px] border border-white/10 bg-white/[.03] px-5 py-4 backdrop-blur-sm transition duration-500 group-hover:-translate-y-0.5 group-hover:border-gold/40 group-hover:bg-white/[.06] md:px-7 md:py-5">
      <span className="font-display w-24 shrink-0 text-4xl leading-none text-gold [font-variant-numeric:lining-nums] md:w-32 md:text-5xl"><Value value={stat.value} animate={animate} /></span>
      <span className="text-sm leading-snug text-white/80 md:text-base">{stat.label}</span>
    </div>
  </li>
}

/** "1,000+" is in the HTML first (no JS, reduced motion); the counter preset animates the number. */
function Value({ value, animate }: { value: string; animate: boolean }) {
  const match = value.match(/^(\d[\d,]*)(\D*)$/)
  if (!match || !animate) return <span dir="ltr">{value}</span>
  return <span dir="ltr" data-counter={match[1].replace(/,/g, '')} data-suffix={match[2]}>{value}</span>
}
