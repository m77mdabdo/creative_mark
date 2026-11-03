import { useMemo, useState } from 'react'
import CaseCard from '@/components/CaseCard'
import { useSite } from '@/hooks/useSite'
import { groupMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/** Case studies as alternating editorial rows, filterable by sector. */
export default function CaseGrid(props: SectionProps) {
  const { section, page } = props
  const { t } = useSite()
  const cases = page.cases ?? []
  const sectors = useMemo(() => [...new Set(cases.map(c => c.sector))], [cases])
  const [sector, setSector] = useState<string>('all')
  const shown = sector === 'all' ? cases : cases.filter(c => c.sector === sector)

  return <SectionShell {...props} className="scroll-mt-16">
    <div className="wrap">
      <div className="mb-12 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <SectionHeading section={section} />
        <div role="group" aria-label={t('filterCases')} className="-mx-5 flex gap-2 overflow-x-auto px-5 pb-1 lg:mx-0 lg:flex-wrap lg:justify-end lg:px-0">
          {['all', ...sectors].map(s => <button key={s} type="button" aria-pressed={sector === s} onClick={() => setSector(s)}
            className={`min-h-11 shrink-0 rounded-full border px-4 text-sm transition ${sector === s ? 'border-gold bg-gold text-ink' : 'border-white/15 text-white/75 hover:border-gold/60 hover:text-white'}`}>
            {s === 'all' ? t('all') : page.industries[s] ?? s}
          </button>)}
        </div>
      </div>
      <ul key={sector} className="space-y-6" {...groupMotion(section)} aria-live="polite">
        {shown.map((item, i) => <li key={item.slug}>
          <CaseCard item={item} media={page.media[item.media]} industries={page.industries} countries={page.countries} regions={page.regions} reverse={i % 2 === 1} />
        </li>)}
      </ul>
    </div>
  </SectionShell>
}
