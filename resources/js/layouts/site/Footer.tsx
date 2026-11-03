import Logo from '@/components/Logo'
import SmartLink from '@/components/SmartLink'
import SocialIcon, { SOCIAL_LABELS } from '@/components/SocialIcon'
import { useSite } from '@/hooks/useSite'

const FOOTER_SERVICES = 6

export default function Footer() {
  const { site, t } = useSite()
  const social = Object.entries(site.social).filter((entry): entry is [string, string] => !!entry[1])
  const linkClass = 'inline-flex min-h-11 items-center hover:text-gold'

  return <footer className="border-t border-white/[.06] bg-[#070707] text-white/70">
    <div className="wrap pb-10 pt-20">
      <div className="grid gap-12 md:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_1.4fr]">
        <div>
          <Logo className="h-14" />
          <p className="mt-6 font-display text-2xl text-white">{t('tagline')}</p>
          {social.length > 0 && <div className="mt-8">
            <p className="eyebrow mb-4">{t('follow')}</p>
            <div className="flex gap-3">
              {social.map(([network, url]) => <a key={network} href={url} target="_blank" rel="noopener noreferrer" aria-label={SOCIAL_LABELS[network] ?? network}
                className="grid h-11 w-11 place-items-center rounded-full border border-white/15 transition hover:border-gold hover:text-gold"><SocialIcon name={network} /></a>)}
            </div>
          </div>}
        </div>

        <div>
          <p className="eyebrow mb-5">{t('company')}</p>
          <ul className="space-y-1 text-sm">
            {site.nav.filter(item => !item.mega).map(item => <li key={item.href}><SmartLink href={item.href} className={linkClass}>{item.label}</SmartLink></li>)}
            <li><SmartLink href="/faq" className={linkClass}>{t('faqLink')}</SmartLink></li>
            <li><SmartLink href="/contact" className={linkClass}>{t('contactLink')}</SmartLink></li>
          </ul>
        </div>

        <div>
          <p className="eyebrow mb-5">{t('solutions')}</p>
          <ul className="space-y-1 text-sm">
            {site.megaServices.slice(0, FOOTER_SERVICES).map(s => <li key={s.slug}><SmartLink href={`/services/${s.slug}`} className={linkClass}>{s.title}</SmartLink></li>)}
            <li><SmartLink href="/services" className={`${linkClass} text-gold`}>{t('allServices')}</SmartLink></li>
          </ul>
        </div>

        <div>
          <p className="eyebrow mb-5">{t('offices')}</p>
          <ul className="space-y-6 text-sm">
            {site.offices.map(office => <li key={office.key}><p className="font-semibold text-white">{office.country}</p><p className="mt-1 leading-relaxed">{office.address}</p></li>)}
            {site.email && <li><a href={`mailto:${site.email}`} dir="ltr" className="hover:text-gold">{site.email}</a></li>}
            {site.phone && <li><a href={`tel:${site.phone.replace(/\s/g, '')}`} dir="ltr" className="hover:text-gold">{site.phone}</a></li>}
          </ul>
        </div>
      </div>

      <div className="mt-16 flex flex-wrap items-center justify-between gap-4 border-t border-white/[.06] pt-8 text-xs text-white/45">
        <p>© {new Date().getFullYear()} Creative Mark. {t('rights')}</p>
        <nav className="flex flex-wrap gap-x-6" aria-label={t('privacyLink')}>
          <SmartLink href="/privacy" className="inline-flex min-h-11 items-center hover:text-gold">{t('privacyLink')}</SmartLink>
          <SmartLink href="/terms" className="inline-flex min-h-11 items-center hover:text-gold">{t('termsLink')}</SmartLink>
        </nav>
        <p className="max-w-md">{t('mapCredit')} · {t('photoNote')}</p>
        <p lang="en" dir="ltr">Creating The Future</p>
      </div>
    </div>
  </footer>
}
