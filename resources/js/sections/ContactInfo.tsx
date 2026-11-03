import { CalendarDays, MapPin, Mail, Phone, MessageCircle } from 'lucide-react'
import Media from '@/components/Media'
import SmartLink from '@/components/SmartLink'
import SocialIcon, { SOCIAL_LABELS } from '@/components/SocialIcon'
import { useSite } from '@/hooks/useSite'
import { groupMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/**
 * Offices and contact channels from the site settings: each office with its photograph and a maps link,
 * then booking, email, phone, WhatsApp and social links (channels without a value are not shown).
 */
export default function ContactInfo(props: SectionProps) {
  const { section, page } = props
  const { site, t } = useSite()
  const photos = [section.media, section.media2]
  const social = Object.entries(site.social).filter((e): e is [string, string] => !!e[1])
  const channel = 'flex min-h-16 items-center gap-4 rounded-[var(--cm-radius)] border border-white/12 px-5 py-4 transition hover:border-gold/60 hover:bg-white/[.03]'

  return <SectionShell {...props}>
    <div className="wrap">
      <SectionHeading section={section} className="mb-14" />
      <div className="grid gap-6 md:grid-cols-2" {...groupMotion(section)}>
        {site.offices.map((office, i) => <article key={office.key} className="group overflow-hidden rounded-[var(--cm-radius)] border border-white/10 bg-white/[.02]">
          <div className="relative aspect-[16/9] overflow-hidden">
            <Media media={photos[i] ? page.media[photos[i]!] : undefined} sizes="(min-width: 768px) 45vw, 100vw" className="h-full w-full transition duration-700 group-hover:scale-105" />
            <div className="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent" aria-hidden />
            <p className="font-display absolute bottom-5 start-6 text-3xl text-white">{office.country}</p>
          </div>
          <div className="flex flex-col gap-5 p-6">
            <p className="flex gap-3 leading-relaxed text-white/80"><MapPin size={18} className="mt-1 shrink-0 text-gold" aria-hidden />{office.address}</p>
            <a href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(office.address)}`} target="_blank" rel="noopener noreferrer"
              className="btn btn-outline self-start">{t('openInMaps')}</a>
          </div>
        </article>)}
      </div>

      <div className="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <SmartLink href="/consultation" className={`${channel} border-gold/50 bg-gold/[.06]`}>
          <CalendarDays size={20} className="shrink-0 text-gold" aria-hidden />
          <span><span className="block font-medium text-white">{t('bookOnline')}</span><span className="text-xs text-white/55">{t('bookOnlineText')}</span></span>
        </SmartLink>
        {site.email && <a href={`mailto:${site.email}`} className={channel}><Mail size={20} className="shrink-0 text-gold" aria-hidden /><span dir="ltr" className="truncate">{site.email}</span></a>}
        {site.phone && <a href={`tel:${site.phone.replace(/\s/g, '')}`} className={channel}><Phone size={20} className="shrink-0 text-gold" aria-hidden /><span dir="ltr">{site.phone}</span></a>}
        {site.whatsapp && <a href={`https://wa.me/${site.whatsapp.replace(/\D/g, '')}`} target="_blank" rel="noopener noreferrer" className={channel}><MessageCircle size={20} className="shrink-0 text-gold" aria-hidden />WhatsApp</a>}
        {social.map(([network, url]) => <a key={network} href={url} target="_blank" rel="noopener noreferrer" className={channel}>
          <span className="shrink-0 text-gold"><SocialIcon name={network} /></span>{SOCIAL_LABELS[network] ?? network}
        </a>)}
      </div>
    </div>
  </SectionShell>
}
