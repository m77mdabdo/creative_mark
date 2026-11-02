import { ArrowUpRight } from 'lucide-react'
import Media from '@/components/Media'
import SmartLink from '@/components/SmartLink'
import { useSite } from '@/hooks/useSite'
import { HighlightedTitle } from '../shared/SectionHeading'
import { headingMotion } from '../shared/motionAttributes'
import type { SectionProps } from '../shared/types'
import { categoryKey, formatNewsDate } from './format'
import NewsTicker from './NewsTicker'

/** The lead story: the newest featured article, otherwise the newest one. */
export const leadStory = (articles: SectionProps['page']['articles'] = []) => articles.find(a => a.featured) ?? articles[0]

/**
 * Newsroom masthead: title and date line, the scrolling "Latest" ticker, then the lead story as a
 * full-bleed photograph with three more headlines beside it.
 */
export default function NewsLead({ section, page }: SectionProps) {
  const { t, locale } = useSite()
  const articles = page.articles ?? []
  const lead = leadStory(articles)
  const others = articles.filter(a => a !== lead).slice(0, 3)
  const today = new Date().toISOString().slice(0, 10)

  return <section id={section.id} aria-labelledby={`${section.id}-title`} className="th-dark relative overflow-hidden">
    <div className="wrap pb-10 pt-32 md:pb-14 md:pt-40" {...headingMotion(section)}>
      <div className="mb-8 flex flex-wrap items-center justify-between gap-3 border-b border-white/10 pb-4 text-xs text-white/55">
        <p className="eyebrow flex items-center gap-3"><span className="h-px w-8 bg-current" aria-hidden />{section.eyebrow}</p>
        <p><time dateTime={today}>{formatNewsDate(today, locale.tag)}</time> · {articles.length} {t('stories')}</p>
      </div>
      <div className="grid gap-6 lg:grid-cols-[1.4fr_1fr] lg:items-end lg:gap-16">
        <h1 id={`${section.id}-title`} className="font-display t-display-xl"><HighlightedTitle title={section.title ?? ''} highlight={section.highlight} /></h1>
        {section.subtitle && <p className="t-body-l muted measure">{section.subtitle}</p>}
      </div>
    </div>

    <NewsTicker items={articles.slice(0, 8)} />

    {lead && <div className="wrap grid gap-6 py-10 md:py-14 lg:grid-cols-[2fr_1fr] lg:gap-8">
      <SmartLink href={`/news/${lead.slug}`} className="group relative block min-h-[460px] overflow-hidden rounded-[var(--cm-radius)] md:min-h-[560px]">
        <Media media={page.media[lead.media]} eager sizes="(min-width: 1024px) 66vw, 100vw"
          className="absolute inset-0 h-full w-full transition duration-[1.6s] ease-[var(--ease-out)] group-hover:scale-[1.04]" data-motion="zoom-in" />
        <div className="absolute inset-0 bg-gradient-to-t from-black via-black/55 to-black/5" aria-hidden />
        <div className="absolute inset-x-0 bottom-0 p-6 md:p-10">
          <p className="mb-4 flex flex-wrap items-center gap-3 text-xs">
            <span className="rounded-full bg-gold px-3 py-1 font-semibold text-ink">{t(categoryKey(lead.category))}</span>
            <span className="text-white/70"><time dateTime={lead.date}>{formatNewsDate(lead.date, locale.tag)}</time> · {lead.source.name}</span>
          </p>
          <h2 className="font-display max-w-3xl text-3xl leading-tight text-white transition group-hover:text-gold md:text-5xl">{lead.title}</h2>
          <p className="mt-4 max-w-2xl text-white/75 md:text-lg">{lead.excerpt}</p>
          <span className="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-gold">{t('readStory')}<ArrowUpRight size={16} className="flip-rtl" aria-hidden /></span>
        </div>
      </SmartLink>

      {others.length > 0 && <aside aria-labelledby={`${section.id}-also`} className="flex flex-col">
        <h2 id={`${section.id}-also`} className="eyebrow mb-4">{t('alsoInNews')}</h2>
        <ol className="flex flex-1 flex-col divide-y divide-white/10 border-y border-white/10">
          {others.map((a, i) => <li key={a.slug} className="flex-1">
            <SmartLink href={`/news/${a.slug}`} className="group flex h-full gap-4 py-5">
              <span className="font-display text-3xl leading-none text-transparent [-webkit-text-stroke:1px_rgba(235,189,108,.8)]" aria-hidden>{String(i + 2).padStart(2, '0')}</span>
              <span className="min-w-0">
                <span className="mb-1 block text-xs text-gold">{t(categoryKey(a.category))} · <time dateTime={a.date}>{formatNewsDate(a.date, locale.tag, 'short')}</time></span>
                <span className="block text-lg leading-snug transition group-hover:text-gold">{a.title}</span>
              </span>
            </SmartLink>
          </li>)}
        </ol>
      </aside>}
    </div>}
  </section>
}
