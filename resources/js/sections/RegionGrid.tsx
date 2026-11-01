import RegionCard from '@/components/RegionCard'
import { useSite } from '@/hooks/useSite'
import { groupMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/** The first card is wide; with 13 regions the last one is wide on three columns too, so no row is left half empty. */
const spanFor = (i: number, total: number) => [
  i === 0 && total % 2 === 1 ? 'sm:col-span-2' : '',
  (i === 0 || i === total - 1) && total % 3 === 1 ? 'lg:col-span-2' : '',
].join(' ')

/** All 13 regions as photo cards linking to their pages (FR-050). */
export default function RegionGrid(props: SectionProps) {
  const { section, page } = props
  const { t } = useSite()

  return <SectionShell {...props}>
    <div className="wrap">
      <SectionHeading section={section} className="mb-14" />
      <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" {...groupMotion(section)}>
        {page.regions.map((region, i) => <li key={region.slug} className={spanFor(i, page.regions.length)}><RegionCard region={region} media={page.media[region.media]} capitalLabel={t('capital')} /></li>)}
      </ul>
    </div>
  </SectionShell>
}
