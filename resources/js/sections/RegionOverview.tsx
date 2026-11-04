import { lazy, Suspense } from 'react'
import TextBlock from '@/components/TextBlock'
import { useSite } from '@/hooks/useSite'
import { HighlightedTitle } from './shared/SectionHeading'
import { headingMotion } from './shared/motionAttributes'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

// The map outline is ~40 KB, so it loads after the page.
const RegionLocator = lazy(() => import('@/components/RegionLocator'))

/**
 * Where a region's opportunity lies: the story with opportunity chips (cards; the section subtitle heads them),
 * beside the region on the map and its leading sectors.
 */
export default function RegionOverview(props: SectionProps) {
  const { section, page } = props
  const { t } = useSite()
  const region = page.region
  const chips = section.cards.filter(c => c.title)

  return <SectionShell {...props}>
    <div className="wrap grid gap-12 lg:grid-cols-[1.2fr_1fr] lg:gap-20">
      <div {...headingMotion(section)}>
        {section.eyebrow && <p className="eyebrow mb-5 flex items-center gap-3"><span className="h-px w-8 bg-current" aria-hidden />{section.eyebrow}</p>}
        {section.title && <h2 id={`${section.id}-title`} className="font-display t-display-l"><HighlightedTitle title={section.title} highlight={section.highlight} /></h2>}
        <div className="gold-rule my-8 w-32" data-motion="line" aria-hidden />
        <div className="space-y-4"><TextBlock text={section.body} className="t-body-l muted measure" /></div>
        {chips.length > 0 && <>
          {section.subtitle && <h3 className="eyebrow mb-4 mt-10">{section.subtitle}</h3>}
          <ul className="flex flex-wrap gap-2">
            {chips.map(c => <li key={c.id} className="rounded-full border border-gold/40 px-4 py-2 text-sm">{c.title}</li>)}
          </ul>
        </>}
      </div>
      {region && <aside className="self-start rounded-[8px] border border-gold/30 bg-gradient-to-b from-white/[.04] to-transparent p-7 md:p-9" data-motion="fade-up">
        <div className="mx-auto max-w-[420px]">
          <Suspense fallback={<div className="aspect-[1000/816]" />}>
            <RegionLocator slug={region.slug} label={`${region.name}: ${t('regionLocation')}`} />
          </Suspense>
        </div>
        {region.industries.length > 0 && <>
          <h3 className="eyebrow mb-4 mt-8">{t('leadingSectors')}</h3>
          <ul className="grid gap-2 sm:grid-cols-2">
            {region.industries.map(i => <li key={i} className="flex items-center gap-3 border-b border-white/[.06] py-2.5 text-sm">
              <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-gold" aria-hidden />{page.industries[i] ?? i}
            </li>)}
          </ul>
        </>}
      </aside>}
    </div>
  </SectionShell>
}
