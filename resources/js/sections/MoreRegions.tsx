import ButtonGroup from '@/components/ButtonGroup'
import RegionCard from '@/components/RegionCard'
import { useSite } from '@/hooks/useSite'
import { groupMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/** The next regions in catalogue order, for further exploration from a region's page. */
export default function MoreRegions(props: SectionProps) {
  const { section, page } = props
  const { t } = useSite()
  const regions = (page.otherRegions ?? []).slice(0, section.limit ?? 3)
  if (!regions.length) return null

  return <SectionShell {...props}>
    <div className="wrap">
      <div className="mb-12 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <SectionHeading section={section} />
        <ButtonGroup buttons={section.buttons} />
      </div>
      <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" {...groupMotion(section)}>
        {regions.map(r => <li key={r.slug}><RegionCard region={r} media={page.media[r.media]} capitalLabel={t('capital')} /></li>)}
      </ul>
    </div>
  </SectionShell>
}
