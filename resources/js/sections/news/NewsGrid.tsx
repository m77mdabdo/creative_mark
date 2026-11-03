import { useMemo, useState } from 'react'
import ButtonGroup from '@/components/ButtonGroup'
import NewsCard from '@/components/NewsCard'
import { useSite } from '@/hooks/useSite'
import type { NewsCategory } from '@/types'
import { groupMotion } from '../shared/motionAttributes'
import SectionHeading from '../shared/SectionHeading'
import SectionShell from '../shared/SectionShell'
import type { SectionProps } from '../shared/types'
import { categoryKey } from './format'
import { leadStory } from './NewsLead'

/**
 * Every story below the lead, filterable by topic. Filtering happens in the browser; links stay crawlable.
 * With a limit (e.g. on the home page) it shows the latest stories only, without filters, and the section's buttons.
 */
export default function NewsGrid(props: SectionProps) {
  const { section, page } = props
  const { t } = useSite()
  const articles = page.articles ?? []
  const lead = leadStory(articles)
  const [topic, setTopic] = useState<NewsCategory | 'all'>('all')
  const topics = useMemo(() => [...new Set(articles.map(a => a.category))], [articles])
  const preview = Boolean(section.limit)
  const shown = preview ? articles.slice(0, section.limit ?? 3)
    : topic === 'all' ? articles.filter(a => a !== lead) : articles.filter(a => a.category === topic)

  return <SectionShell {...props}>
    <div className="wrap">
      <div className="mb-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <SectionHeading section={section} />
        {preview ? <ButtonGroup buttons={section.buttons} /> : <div role="group" aria-label={t('filterNews')} className="-mx-5 flex gap-2 overflow-x-auto px-5 pb-1 lg:mx-0 lg:flex-wrap lg:justify-end lg:px-0">
          {(['all', ...topics] as const).map(c => <button key={c} type="button" aria-pressed={topic === c} onClick={() => setTopic(c)}
            className={`min-h-11 shrink-0 rounded-full border px-4 text-sm transition ${topic === c ? 'border-gold bg-gold text-ink' : 'border-white/15 text-white/75 hover:border-gold/60 hover:text-white'}`}>
            {c === 'all' ? t('allNews') : t(categoryKey(c))}
          </button>)}
        </div>}
      </div>
      <ul key={topic} className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" {...groupMotion(section)} aria-live="polite">
        {shown.map((item, i) => <li key={item.slug} className={!preview && i === 0 && shown.length > 2 ? 'sm:col-span-2' : ''}>
          <NewsCard item={item} media={page.media[item.media]} large={!preview && i === 0 && shown.length > 2} />
        </li>)}
      </ul>
    </div>
  </SectionShell>
}
