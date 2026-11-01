import { ArrowUpRight } from 'lucide-react'
import ButtonGroup from '@/components/ButtonGroup'
import Icon from '@/components/Icon'
import Media from '@/components/Media'
import SmartLink from '@/components/SmartLink'
import { useSite } from '@/hooks/useSite'
import type { MediaItem, ServiceItem } from '@/types'
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

function ServiceCard({ service, media }: { service: ServiceItem; media?: MediaItem }) {
  return <SmartLink href={`/services/${service.slug}`}
    className="card-s group flex h-full flex-col overflow-hidden rounded-[var(--cm-radius)] transition duration-500 hover:-translate-y-1 hover:border-gold/50">
    <div className="relative h-48 overflow-hidden">
      <Media media={media} sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" className="h-full w-full transition duration-[1.1s] group-hover:scale-[1.06]" />
      <div className="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent" aria-hidden />
      <span className="absolute start-4 top-4 grid h-11 w-11 place-items-center rounded-full bg-ink/80 text-gold backdrop-blur" aria-hidden><Icon name={service.icon} size={19} /></span>
    </div>
    <div className="flex flex-1 flex-col p-7">
      <p className="eyebrow mb-2" aria-hidden>{String(service.order).padStart(2, '0')}</p>
      <h3 className="mb-2 text-xl font-semibold">{service.title}</h3>
      <p className="muted line-clamp-3 text-sm leading-relaxed">{service.summary}</p>
      <span className="accent mt-auto inline-flex items-center gap-2 pt-6 text-sm font-semibold">{service.cta}<ArrowUpRight size={15} className="flip-rtl" aria-hidden /></span>
    </div>
  </SmartLink>
}
