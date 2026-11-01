import { useRef } from 'react'
import { ChevronLeft, ChevronRight, Quote } from 'lucide-react'
import { useSite } from '@/hooks/useSite'
import type { TestimonialItem } from '@/types'
import { groupMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/**
 * Testimonials (FR-020). A grid on desktop; on phones a swipe row with previous/next buttons, never auto-advancing.
 * Names appear only with recorded consent (RULE-02), otherwise "Client review".
 */
export default function Testimonials(props: SectionProps) {
  const { section, page } = props
  const { t } = useSite()
  const row = useRef<HTMLUListElement>(null)

  const scroll = (direction: 1 | -1) => {
    const el = row.current
    if (!el) return
    const rtl = document.documentElement.dir === 'rtl' ? -1 : 1
    el.scrollBy({ left: direction * rtl * el.clientWidth * 0.85, behavior: 'smooth' })
  }

  return <SectionShell {...props}>
    <div className="wrap">
      <div className="mb-14"><SectionHeading section={section} center /></div>
      <ul ref={row} className="swipe md:grid md:grid-flow-row md:grid-cols-3 md:gap-6 md:overflow-visible" {...groupMotion(section)}>
        {page.testimonials.map(item => <li key={item.id}><TestimonialCard item={item} /></li>)}
      </ul>
      <div className="mt-6 flex justify-center gap-3 md:hidden">
        <button type="button" onClick={() => scroll(-1)} aria-label={t('prev')} className="hair grid h-11 w-11 place-items-center rounded-full border"><ChevronLeft size={18} className="flip-rtl" aria-hidden /></button>
        <button type="button" onClick={() => scroll(1)} aria-label={t('next')} className="hair grid h-11 w-11 place-items-center rounded-full border"><ChevronRight size={18} className="flip-rtl" aria-hidden /></button>
      </div>
    </div>
  </SectionShell>
}

function TestimonialCard({ item }: { item: TestimonialItem }) {
  const { t } = useSite()
  return <figure className="flex h-full flex-col rounded-[6px] border border-gold/25 bg-gradient-to-b from-white/[.04] to-transparent p-8">
    <Quote className="mb-6 text-gold flip-rtl" size={28} strokeWidth={1.2} aria-hidden />
    <blockquote className="font-display flex-1 text-xl leading-relaxed">“{item.quote}”</blockquote>
    <figcaption className="hair mt-8 border-t pt-5">
      <p className="text-sm font-semibold">{item.author ?? t('clientReview')}</p>
      {item.context && <p className="muted text-xs">{item.context}</p>}
      {!item.author && <p className="muted mt-3 text-[11px]">{t('unverified')}</p>}
    </figcaption>
  </figure>
}
