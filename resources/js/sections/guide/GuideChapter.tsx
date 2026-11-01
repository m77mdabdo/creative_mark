import { Check, Lightbulb } from 'lucide-react'
import Media from '@/components/Media'
import TextBlock from '@/components/TextBlock'
import { useSite } from '@/hooks/useSite'
import { HighlightedTitle } from '../shared/SectionHeading'
import { groupMotion, headingMotion, parallaxMotion } from '../shared/motionAttributes'
import SectionShell from '../shared/SectionShell'
import type { SectionProps } from '../shared/types'
import { chapterNumber, guideChapters } from './chapters'
import SourceLinks from './SourceLinks'

/**
 * One chapter of the investor guide: an outlined chapter number, the story, what to do in practice,
 * key figures, a "good to know" callout and the sources behind every figure.
 * On large screens the photograph stays in view (sticky) while the text scrolls past it.
 */
export default function GuideChapter(props: SectionProps) {
  const { section, page } = props
  const { t } = useSite()
  const chapters = guideChapters(page)
  const number = chapterNumber(chapters.findIndex(c => c.id === section.id) + 1)
  const reverse = section.layout === 'split-reverse'
  const points = (section.points ?? '').split('\n').map(p => p.trim()).filter(Boolean)

  return <SectionShell {...props} className="scroll-mt-16 border-t border-white/[.06]">
    <div className="wrap grid gap-10 lg:grid-cols-12 lg:gap-16">
      <figure className={`relative lg:col-span-5 ${reverse ? 'lg:order-last' : ''}`}>
        <div className="lg:sticky lg:top-28">
          <div className="relative aspect-[16/10] overflow-hidden rounded-[var(--cm-radius)] lg:aspect-[4/5]">
            <Media media={section.media ? page.media[section.media] : undefined} sizes="(min-width: 1024px) 40vw, 100vw"
              className="absolute inset-x-0 -inset-y-[8%] h-[116%] w-full" {...parallaxMotion(section, 6)} />
            <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent" aria-hidden />
            <span className="absolute bottom-4 start-5 font-display text-[88px] leading-none text-transparent [-webkit-text-stroke:1px_rgba(235,189,108,.85)] md:text-[120px] lg:bottom-6 lg:start-7 lg:text-[160px]" aria-hidden>{number}</span>
          </div>
        </div>
      </figure>

      <div className="lg:col-span-7">
        <div {...headingMotion(section)}>
          <p className="eyebrow mb-5 flex items-center gap-3"><span className="h-px w-8 bg-current" aria-hidden />{t('chapter')} {number} · {section.eyebrow}</p>
          <h2 id={`${section.id}-title`} className="font-display t-display-l"><HighlightedTitle title={section.title ?? ''} highlight={section.highlight} /></h2>
          {section.subtitle && <p className="t-body-l mt-6 text-white/90 measure">{section.subtitle}</p>}
        </div>
        <div className="gold-rule my-8 w-32" data-motion="line" aria-hidden />
        <div className="space-y-4" {...headingMotion(section)}><TextBlock text={section.body} className="muted measure leading-relaxed" /></div>

        {points.length > 0 && <div className="mt-10">
          <h3 className="eyebrow mb-4">{t('inPractice')}</h3>
          <ul className="space-y-1" {...groupMotion(section)}>
            {points.map(p => <li key={p} className="flex items-start gap-3 border-b border-white/[.06] py-3 last:border-0">
              <span className="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-gold/15 text-gold" aria-hidden><Check size={14} /></span>
              <span className="measure">{p}</span>
            </li>)}
          </ul>
        </div>}

        {section.cards.length > 0 && <div className="mt-10">
          <h3 className="eyebrow mb-4">{t('keyFacts')}</h3>
          <dl className={`grid gap-px overflow-hidden rounded-[var(--cm-radius)] border border-gold/25 bg-gold/20 sm:grid-cols-2 ${section.cards.length % 3 === 0 ? 'xl:grid-cols-3' : ''}`} {...groupMotion(section)}>
            {section.cards.map(c => <div key={c.id} className="flex flex-col-reverse justify-end gap-2 bg-ink p-5 md:p-6">
              <dt className="text-sm text-white/65">{c.text}</dt>
              <dd className="font-display text-4xl text-gold md:text-5xl">{c.title}</dd>
            </div>)}
          </dl>
        </div>}

        {section.note && <aside className="mt-10 flex gap-4 rounded-[var(--cm-radius)] border-s-2 border-gold bg-gold/[.06] p-5 md:p-6">
          <Lightbulb size={20} className="mt-0.5 shrink-0 text-gold" aria-hidden />
          <div><p className="mb-1 text-sm font-semibold text-gold">{t('goodToKnow')}</p><p className="text-white/80 measure">{section.note}</p></div>
        </aside>}

        <SourceLinks sources={section.sources ?? []} />
      </div>
    </div>
  </SectionShell>
}
