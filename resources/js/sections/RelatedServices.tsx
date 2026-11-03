import ButtonGroup from '@/components/ButtonGroup'
import ServiceCard from '@/components/ServiceCard'
import { groupMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/** Related business solutions of the current solution (chosen on the service record), as cards. */
export default function RelatedServices(props: SectionProps) {
  const { section, page } = props
  const related = (page.related ?? []).slice(0, section.limit ?? 3)
  if (!related.length) return null

  return <SectionShell {...props}>
    <div className="wrap">
      <div className="mb-12 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <SectionHeading section={section} />
        <ButtonGroup buttons={section.buttons} />
      </div>
      <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" {...groupMotion(section)}>
        {related.map(item => <li key={item.slug}><ServiceCard service={item} media={page.media[item.media]} /></li>)}
      </ul>
    </div>
  </SectionShell>
}
