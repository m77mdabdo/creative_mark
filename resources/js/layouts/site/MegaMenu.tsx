import Icon from '@/components/Icon'
import SmartLink from '@/components/SmartLink'
import { useSite } from '@/hooks/useSite'

type Props = { id: string; onMouseEnter: () => void; onMouseLeave: () => void }

/** Full-width "Business solutions" panel: the 11 services plus a feature tile (desktop only). */
export default function MegaMenu({ id, onMouseEnter, onMouseLeave }: Props) {
  const { site, t } = useSite()

  return <div id={id} onMouseEnter={onMouseEnter} onMouseLeave={onMouseLeave}
    className="absolute inset-x-0 top-full hidden border-t border-white/[.06] bg-[#0d0d0e]/97 backdrop-blur-xl xl:block">
    <div className="wrap grid grid-cols-[1fr_320px] gap-10 py-10">
      <ul className="grid grid-cols-3 gap-x-6 gap-y-1">
        {site.megaServices.map(service => <li key={service.slug}>
          <SmartLink href={`/services/${service.slug}`} className="flex items-start gap-3 rounded-md p-3 hover:bg-white/[.04]">
            <span className="mt-0.5 text-gold"><Icon name={service.icon} size={17} /></span>
            <span>
              <span className="block text-sm font-semibold text-white">{service.title}</span>
              <span className="line-clamp-1 block text-xs text-white/50">{service.tagline}</span>
            </span>
          </SmartLink>
        </li>)}
      </ul>

      <SmartLink href="/start-your-company" className="group relative flex min-h-52 flex-col justify-end overflow-hidden rounded-[var(--cm-radius)] border border-white/10 p-6">
        <img src="/media/placeholders/cm-handshake-800.webp" alt="" className="absolute inset-0 h-full w-full object-cover opacity-50 transition duration-700 group-hover:scale-105" />
        <span className="absolute inset-0 bg-gradient-to-t from-ink via-ink/40 to-transparent" />
        <span className="relative font-display text-2xl text-white">{t('startCompany')}</span>
        <span className="relative mt-1 text-xs text-white/70">{t('startCompanyText')}</span>
      </SmartLink>
    </div>
  </div>
}
