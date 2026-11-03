import { Head } from '@inertiajs/react'
import Media from '@/components/Media'
import SmartLink from '@/components/SmartLink'
import { useSite } from '@/hooks/useSite'
import SiteLayout from '@/layouts/SiteLayout'
import type { MediaItem } from '@/types'

/** Branded error page (404, 403, 500, 503) with the most useful next steps. */
export default function ErrorPage({ status, media }: { status: number; media: Record<string, MediaItem> }) {
  const { t, site } = useSite()
  const missing = status === 404 || status === 403
  const title = missing ? t('notFoundTitle') : t('serverErrorTitle')
  const links = [
    { href: '/start-your-company', label: t('startCompany') },
    { href: '/services', label: t('allServices') },
    { href: '/investor-guide', label: t('readGuide') },
    { href: '/faq', label: t('faqLink') },
  ]

  return <SiteLayout>
    <Head><title>{`${title} | Creative Mark`}</title><meta name="robots" content="noindex" /></Head>
    <section className="th-dark relative flex min-h-[100svh] items-center overflow-hidden">
      <Media media={media['cm-desert-road']} eager sizes="100vw" className="absolute inset-0 h-full w-full opacity-45" />
      <div className="absolute inset-0 bg-gradient-to-b from-black/85 via-black/60 to-black" aria-hidden />
      <div className="wrap relative py-32">
        <p className="font-display text-[clamp(6rem,22vw,16rem)] leading-none text-transparent [-webkit-text-stroke:1px_var(--cm-gold)]" aria-hidden>{status}</p>
        <h1 className="font-display t-display-l mt-4 max-w-3xl text-white">{title}</h1>
        <p className="t-body-l mt-5 max-w-xl text-white/70">{missing ? t('notFoundText') : t('serverErrorText')}</p>
        <div className="mt-10 flex flex-wrap gap-3">
          <SmartLink href="/" className="btn btn-gold">{t('backHome')}</SmartLink>
          <SmartLink href="/consultation" className="btn btn-outline">{site.cta?.label ?? t('bookTitle')}</SmartLink>
        </div>
        {missing && <ul className="mt-14 grid max-w-3xl gap-px overflow-hidden rounded-[var(--cm-radius)] border border-white/10 bg-white/10 sm:grid-cols-2">
          {links.map(l => <li key={l.href}><SmartLink href={l.href} className="flex min-h-14 items-center justify-between bg-black/70 px-5 text-sm text-white/80 transition hover:bg-black/40 hover:text-gold">{l.label}<span aria-hidden className="flip-rtl">→</span></SmartLink></li>)}
        </ul>}
      </div>
    </section>
  </SiteLayout>
}
