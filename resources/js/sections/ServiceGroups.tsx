import ServiceCard from '@/components/ServiceCard'
import type { ServiceGroup } from '@/types'
import { groupMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

const ORDER: ServiceGroup[] = ['setup', 'grow', 'comply']

/** All business solutions, grouped Set up / Grow / Comply in presentation order (Gate 3, G3-6). */
export default function ServiceGroups(props: SectionProps) {
  const { section, page } = props

  return <SectionShell {...props}>
    <div className="wrap">
      <SectionHeading section={section} className="mb-16" />
      <div className="space-y-20">
        {ORDER.map((group, index) => {
          const services = page.services.filter(s => s.group === group)
          if (!services.length) return null
          return <div key={group} className="grid gap-8 lg:grid-cols-[220px_1fr] lg:gap-12">
            <div className="lg:sticky lg:top-28 lg:self-start">
              <p className="font-display text-5xl text-gold/50" aria-hidden>{String(index + 1).padStart(2, '0')}</p>
              <h3 className="font-display t-display-m mt-2">{page.serviceGroups[group]}</h3>
              <span className="mt-5 block h-px w-16 bg-gold" aria-hidden />
            </div>
            <ul className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3" {...groupMotion(section)}>
              {services.map(service => <li key={service.slug}><ServiceCard service={service} media={page.media[service.media]} /></li>)}
            </ul>
          </div>
        })}
      </div>
    </div>
  </SectionShell>
}
