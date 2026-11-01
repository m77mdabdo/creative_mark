import ButtonGroup from '@/components/ButtonGroup'
import ServiceCard from '@/components/ServiceCard'
import SmartLink from '@/components/SmartLink'
import { useSite } from '@/hooks/useSite'
import { groupMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

const MOBILE_VISIBLE = 4 // phones show the first four, then "All business solutions"

/** Services overview (FR-016), order and visibility from the CMS. */
export default function Services(props: SectionProps) {
  const { section, page } = props
  const { t } = useSite()
  const services = page.services.slice(0, section.limit ?? page.services.length)

  return <SectionShell {...props}>
    <div className="wrap">
      <div className="mb-14 flex flex-wrap items-end justify-between gap-6">
        <SectionHeading section={section} />
        <ButtonGroup buttons={section.buttons} className="hidden md:flex" />
      </div>
      <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" {...groupMotion(section)}>
        {services.map((service, i) => <li key={service.slug} className={i >= MOBILE_VISIBLE ? 'hidden sm:block' : ''}>
          <ServiceCard service={service} media={page.media[service.media]} />
        </li>)}
      </ul>
      <SmartLink href="/services" className="btn btn-outline mt-8 w-full md:hidden">{t('allServices')}</SmartLink>
    </div>
  </SectionShell>
}
