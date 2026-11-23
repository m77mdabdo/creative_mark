import type { ReactNode } from 'react'
import { ArrowUpRight, CalendarCheck, MapPin, Phone } from 'lucide-react'
import Logo from '@/components/Logo'
import SmartLink from '@/components/SmartLink'
import SocialIcon, { SOCIAL_LABELS } from '@/components/SocialIcon'
import WhatsAppGlyph from '@/components/WhatsAppGlyph'
import { useSite } from '@/hooks/useSite'

const FOOTER_SERVICES = 6

type ActionProps = { href: string; icon: ReactNode; label: string; detail?: string; primary?: boolean; external?: boolean }

/** One contact button: icon disc, label and an optional detail line (phone number). The primary one is filled gold. */
function ContactAction({ href, icon, label, detail, primary = false, external = false }: ActionProps) {
  const tone = primary
    ? 'border-gold bg-gold text-ink hover:bg-gold-soft'
    : 'border-white/12 bg-white/[.03] text-white hover:border-gold/60 hover:bg-white/[.06]'
  const content = <>
    <span className={`grid h-11 w-11 shrink-0 place-items-center rounded-full transition ${primary ? 'bg-ink/10' : 'border border-gold/35 text-gold group-hover:bg-gold group-hover:text-ink'}`}>{icon}</span>
    <span className="min-w-0 flex-1 text-start">
      <span className="block font-semibold leading-tight">{label}</span>
      {detail && <span dir="ltr" className={`mt-1 block text-sm ${primary ? 'text-ink/70' : 'text-white/55'}`}>{detail}</span>}
    </span>
    <ArrowUpRight size={18} strokeWidth={1.5} aria-hidden className={`shrink-0 transition group-hover:-translate-y-0.5 group-hover:translate-x-0.5 rtl:-scale-x-100 rtl:group-hover:-translate-x-0.5 ${primary ? '' : 'text-gold'}`} />
  </>
  const className = `group flex min-h-16 items-center gap-4 rounded-2xl border px-4 py-3 transition duration-300 motion-reduce:transition-none ${tone}`

  return external
    ? <a href={href} target="_blank" rel="noopener noreferrer" className={className}>{content}</a>
    : href.startsWith('/') ? <SmartLink href={href} className={className}>{content}</SmartLink> : <a href={href} className={className}>{content}</a>
}

/**
 * Two-tier footer split by an inset gold rule.
 * Above the rule: the contact buttons at the reading start and the logo at the end, so the layout mirrors
 * itself between RTL (logo on the left) and LTR (logo on the right). Below the rule: offices, links and legal.
 * The call button uses the CMS phone number, falling back to the WhatsApp number; WhatsApp shows once its number is set.
 */
export default function Footer() {
  const { site, t } = useSite()
  const social = Object.entries(site.social).filter((entry): entry is [string, string] => !!entry[1])
  const whatsappDigits = (site.whatsapp ?? '').replace(/\D/g, '')
  // Until a separate phone number is set in the CMS, "Call us" dials the WhatsApp number.
  const phone = site.phone || site.whatsapp
  const linkClass = 'inline-flex min-h-11 items-center hover:text-gold'

  return <footer className="border-t border-white/[.06] bg-[#070707] text-white/70">
    <div className="wrap pb-10 pt-16 md:pt-20">
      <div className="flex flex-col-reverse gap-12 lg:flex-row lg:items-center lg:justify-between lg:gap-16">
        <div className="flex-1 lg:max-w-4xl">
          <p className="eyebrow">{t('contactLink')}</p>
          <p className="mt-3 font-display text-2xl text-white md:text-3xl">{t('footerContactTitle')}</p>
          <div className="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            {phone && <ContactAction href={`tel:${phone.replace(/[^\d+]/g, '')}`} label={t('callUs')} detail={phone}
              icon={<Phone size={18} strokeWidth={1.5} aria-hidden />} />}
            {whatsappDigits && <ContactAction href={`https://wa.me/${whatsappDigits}?text=${encodeURIComponent(t('whatsappGreeting'))}`} external label={t('whatsappShort')} detail={site.whatsapp ?? undefined}
              icon={<WhatsAppGlyph className="h-5 w-5" />} />}
            <ContactAction href="/consultation" primary label={t('bookConsultation')} icon={<CalendarCheck size={18} strokeWidth={1.6} aria-hidden />} />
          </div>
        </div>

        <div className="flex flex-col items-start gap-5 lg:items-end lg:text-end">
          <Logo className="h-14 md:h-16" />
          <p className="max-w-xs font-display text-2xl text-white">{t('tagline')}</p>
        </div>
      </div>

      <div aria-hidden className="mx-auto my-12 h-0.5 w-[88%] rounded-full bg-[linear-gradient(90deg,transparent,var(--cm-gold)_10%,var(--cm-gold)_90%,transparent)] shadow-[0_0_14px_rgba(235,189,108,.35)] md:my-14 md:w-[80%]" />

      <div className="grid gap-12 sm:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1.4fr]">
        <div>
          <p className="eyebrow mb-5">{t('offices')}</p>
          <address className="space-y-5 text-sm not-italic">
            {site.offices.map(office => <div key={office.key} className="flex gap-3">
              <MapPin size={18} strokeWidth={1.5} className="mt-0.5 shrink-0 text-gold" aria-hidden />
              <div><p className="font-semibold text-white">{office.country}</p><p className="mt-1 leading-relaxed">{office.address}</p></div>
            </div>)}
            {site.email && <a href={`mailto:${site.email}`} dir="ltr" className="inline-block hover:text-gold">{site.email}</a>}
          </address>
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
          <ul className="columns-2 gap-6 space-y-1 text-sm sm:columns-1 lg:columns-1">
            {site.nav.filter(item => !item.mega).map(item => <li key={item.href} className="break-inside-avoid"><SmartLink href={item.href} className={linkClass}>{item.label}</SmartLink></li>)}
            <li className="break-inside-avoid"><SmartLink href="/faq" className={linkClass}>{t('faqLink')}</SmartLink></li>
            <li className="break-inside-avoid"><SmartLink href="/contact" className={linkClass}>{t('contactLink')}</SmartLink></li>
          </ul>
        </div>

        <div className="sm:col-span-2 lg:col-span-1">
          <p className="eyebrow mb-5">{t('solutions')}</p>
          <ul className="columns-2 gap-6 space-y-1 text-sm sm:gap-10 lg:columns-1">
            {site.megaServices.slice(0, FOOTER_SERVICES).map(s => <li key={s.slug} className="break-inside-avoid"><SmartLink href={`/services/${s.slug}`} className={linkClass}>{s.title}</SmartLink></li>)}
            <li className="break-inside-avoid"><SmartLink href="/services" className={`${linkClass} text-gold`}>{t('allServices')}</SmartLink></li>
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
