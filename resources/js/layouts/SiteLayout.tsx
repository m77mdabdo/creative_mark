import { useEffect, useRef, useState, type ReactNode } from 'react'
import { Link, router } from '@inertiajs/react'
import { ChevronDown, Globe, Languages, Menu, MessageSquareText, X } from 'lucide-react'
import { Icon, Logo, SmartLink, SocialIcon } from '@/components/ui'
import { useSite } from '@/lib/useSite'
import { useSmoothScroll } from '@/motion/useMotion'

function LangSwitch() {
  const { locale } = useSite()
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)
  useEffect(() => {
    if (!open) return
    const off = (e: MouseEvent | KeyboardEvent) => {
      if (e instanceof KeyboardEvent ? e.key === 'Escape' : !ref.current?.contains(e.target as Node)) setOpen(false)
    }
    document.addEventListener('mousedown', off); document.addEventListener('keydown', off)
    return () => { document.removeEventListener('mousedown', off); document.removeEventListener('keydown', off) }
  }, [open])
  const path = typeof window === 'undefined' ? '' : window.location.pathname.replace(/^\/[a-z]{2}(?=\/|$)/, '')
  return <div ref={ref} className="relative">
    <button type="button" onClick={() => setOpen(!open)} aria-expanded={open} aria-haspopup="true" data-testid="lang-switch"
      className="flex h-11 items-center gap-1.5 rounded-full border border-white/20 px-3.5 text-xs font-semibold uppercase transition hover:border-gold">
      <Globe size={14} aria-hidden /><span lang={locale.tag}>{locale.code}</span>
    </button>
    {open && <ul className="absolute end-0 top-13 z-60 w-48 overflow-hidden rounded-md border border-white/10 bg-[#111]/98 py-1 shadow-2xl backdrop-blur">
      {locale.available.map(l => <li key={l.code}>
        <a href={`/${l.code}${path}`} lang={l.tag} hrefLang={l.tag} aria-current={l.code === locale.code ? 'true' : undefined}
          className={`flex min-h-11 items-center justify-between px-4 text-sm hover:bg-white/5 ${l.code === locale.code ? 'text-gold' : 'text-white/85'}`}>
          <span>{l.native}</span><span className="text-[10px] uppercase text-white/40">{l.code}</span>
        </a>
      </li>)}
    </ul>}
  </div>
}

function Header() {
  const { site, L, href } = useSite()
  const [solid, setSolid] = useState(false)
  const [hidden, setHidden] = useState(false)
  const [drawer, setDrawer] = useState(false)
  const [mega, setMega] = useState(false)
  const headerRef = useRef<HTMLElement>(null)
  const drawerRef = useRef<HTMLDivElement>(null)
  const timer = useRef<number | undefined>(undefined)

  useEffect(() => {
    let last = window.scrollY
    const on = () => {
      const y = window.scrollY
      setSolid(y > 80)
      // hide on fast downward scroll, return on any upward scroll; never while focus is inside
      const focusInside = headerRef.current?.contains(document.activeElement)
      if (!focusInside) setHidden(y > 400 && y - last > 12 ? true : y < last ? false : hidden)
      last = y
    }
    on(); window.addEventListener('scroll', on, { passive: true })
    return () => window.removeEventListener('scroll', on)
  }, [hidden])

  useEffect(() => router.on('navigate', () => { setDrawer(false); setMega(false) }), [])

  // drawer: focus trap, Esc, scroll lock, inert background
  useEffect(() => {
    if (!drawer) return
    const el = drawerRef.current!
    const main = document.getElementById('main'); const foot = document.querySelector('footer')
    main?.setAttribute('inert', ''); foot?.setAttribute('inert', '')
    document.body.style.overflow = 'hidden'
    const focusables = () => Array.from(el.querySelectorAll<HTMLElement>('a[href],button:not([disabled])'))
    focusables()[0]?.focus()
    const key = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setDrawer(false)
      if (e.key !== 'Tab') return
      const f = focusables(); const first = f[0], last = f[f.length - 1]
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus() }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus() }
    }
    document.addEventListener('keydown', key)
    return () => {
      document.removeEventListener('keydown', key); document.body.style.overflow = ''
      main?.removeAttribute('inert'); foot?.removeAttribute('inert')
      document.querySelector<HTMLElement>('[data-testid="menu-button"]')?.focus()
    }
  }, [drawer])

  const openMega = () => { window.clearTimeout(timer.current); timer.current = window.setTimeout(() => setMega(true), 150) }
  const closeMega = () => { window.clearTimeout(timer.current); timer.current = window.setTimeout(() => setMega(false), 120) }

  return <>
    <header ref={headerRef}
      className={`fixed inset-x-0 top-0 z-50 text-white transition-[transform,background-color,height] duration-500 ${hidden && !drawer ? '-translate-y-full' : ''} ${solid ? 'bg-ink/92 backdrop-blur-xl border-b border-white/[.06]' : 'bg-gradient-to-b from-black/55 to-transparent'}`}
      onKeyDown={e => { if (e.key === 'Escape' && mega) { setMega(false); headerRef.current?.querySelector<HTMLElement>('[data-mega-trigger]')?.focus() } }}>
      <div className={`wrap flex items-center gap-4 transition-[height] duration-500 ${solid ? 'h-16' : 'h-16 xl:h-[88px]'}`}>
        <Link href={href('/')} aria-label={L('home')} className="shrink-0"><Logo className="h-9 xl:h-11" /></Link>
        <nav className="ms-auto hidden xl:flex items-center" aria-label={L('mainNav')}>
          {site.nav.map(n => n.mega === 'services'
            ? <div key={n.href} className="relative" onMouseEnter={openMega} onMouseLeave={closeMega}>
                <button type="button" data-mega-trigger aria-expanded={mega} aria-controls="mega-services" onClick={() => setMega(!mega)}
                  className="flex min-h-11 items-center gap-1 px-3 text-[13px] font-medium text-white/85 transition hover:text-gold">
                  {n.label}<ChevronDown size={13} className={`transition ${mega ? 'rotate-180' : ''}`} aria-hidden />
                </button>
              </div>
            : <SmartLink key={n.href} href={n.href} className="flex min-h-11 items-center px-3 text-[13px] font-medium text-white/85 transition hover:text-gold">{n.label}</SmartLink>)}
        </nav>
        <div className="ms-auto xl:ms-3 flex items-center gap-2">
          <LangSwitch />
          {site.cta && <SmartLink href={site.cta.href} className="btn btn-gold hidden sm:inline-flex !min-h-11 !px-4 !py-2 !text-[11px]">{site.cta.label}</SmartLink>}
          {site.cta && <SmartLink href={site.cta.href} aria-label={site.cta.label} className="grid h-11 w-11 place-items-center rounded-[var(--cm-radius)] bg-gold text-ink sm:hidden"><MessageSquareText size={18} aria-hidden /></SmartLink>}
          <button type="button" data-testid="menu-button" className="grid h-11 w-11 place-items-center xl:hidden" onClick={() => setDrawer(true)} aria-label={L('menu')} aria-expanded={drawer}><Menu aria-hidden /></button>
        </div>
      </div>
      {mega && <div id="mega-services" className="absolute inset-x-0 top-full hidden xl:block border-t border-white/[.06] bg-[#0d0d0e]/97 backdrop-blur-xl" onMouseEnter={openMega} onMouseLeave={closeMega}>
        <div className="wrap grid grid-cols-[1fr_320px] gap-10 py-10">
          <ul className="grid grid-cols-3 gap-x-6 gap-y-1">
            {site.megaServices.map(s => <li key={s.slug}><SmartLink href={`/services/${s.slug}`} className="flex items-start gap-3 rounded-md p-3 hover:bg-white/[.04]">
              <span className="mt-0.5 text-gold"><Icon name={s.icon} size={17} /></span>
              <span><span className="block text-sm font-semibold text-white">{s.title}</span><span className="block text-xs text-white/50 line-clamp-1">{s.tagline}</span></span>
            </SmartLink></li>)}
          </ul>
          <SmartLink href="/start-your-company" className="group relative flex flex-col justify-end overflow-hidden rounded-[var(--cm-radius)] border border-white/10 p-6 min-h-52">
            <img src="/media/placeholders/cm-handshake-800.webp" alt="" className="absolute inset-0 h-full w-full object-cover opacity-50 transition duration-700 group-hover:scale-105" />
            <span className="absolute inset-0 bg-gradient-to-t from-ink via-ink/40 to-transparent" />
            <span className="relative font-display text-2xl text-white">{L('startCompany')}</span>
            <span className="relative mt-1 text-xs text-white/70">{L('startCompanyText')}</span>
          </SmartLink>
        </div>
      </div>}
    </header>

    {drawer && <div ref={drawerRef} className="fixed inset-0 z-60 overflow-y-auto bg-ink text-white" role="dialog" aria-modal="true" aria-label={L('menu')}>
      <div className="wrap flex h-16 items-center justify-between"><Logo className="h-9" /><button type="button" className="grid h-11 w-11 place-items-center" onClick={() => setDrawer(false)} aria-label={L('close')}><X aria-hidden /></button></div>
      <nav className="wrap pb-16" aria-label={L('mainNav')}>
        {site.nav.map(n => <div key={n.href} className="border-b border-white/10">
          <SmartLink href={n.href} className="flex min-h-14 items-center font-display text-3xl">{n.label}</SmartLink>
          {n.mega === 'services' && <ul className="grid gap-1 pb-4">{site.megaServices.map(s => <li key={s.slug}><SmartLink href={`/services/${s.slug}`} className="flex min-h-11 items-center gap-3 text-sm text-white/65"><Icon name={s.icon} size={15} className="text-gold" />{s.title}</SmartLink></li>)}</ul>}
        </div>)}
        {site.cta && <SmartLink href={site.cta.href} className="btn btn-gold mt-8 w-full">{site.cta.label}</SmartLink>}
      </nav>
    </div>}
  </>
}

function Footer() {
  const { site, L } = useSite()
  const social = Object.entries(site.social).filter(([, v]) => !!v) as [string, string][]
  return <footer className="border-t border-white/[.06] bg-[#070707] text-white/70">
    <div className="wrap pt-20 pb-10">
      <div className="grid gap-12 md:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_1.4fr]">
        <div>
          <Logo className="h-14" />
          <p className="mt-6 font-display text-2xl text-white">{L('tagline')}</p>
          {social.length > 0 && <div className="mt-8"><p className="eyebrow mb-4">{L('follow')}</p><div className="flex gap-3">
            {social.map(([k, v]) => <a key={k} href={v} target="_blank" rel="noopener noreferrer" aria-label={k[0].toUpperCase() + k.slice(1)} className="grid h-11 w-11 place-items-center rounded-full border border-white/15 transition hover:border-gold hover:text-gold"><SocialIcon name={k} /></a>)}
          </div></div>}
        </div>
        <div><p className="eyebrow mb-5">{L('company')}</p><ul className="space-y-1 text-sm">
          {site.nav.filter(n => !n.mega).map(n => <li key={n.href}><SmartLink href={n.href} className="inline-flex min-h-11 items-center hover:text-gold">{n.label}</SmartLink></li>)}
        </ul></div>
        <div><p className="eyebrow mb-5">{L('solutions')}</p><ul className="space-y-1 text-sm">
          {site.megaServices.slice(0, 6).map(s => <li key={s.slug}><SmartLink href={`/services/${s.slug}`} className="inline-flex min-h-11 items-center hover:text-gold">{s.title}</SmartLink></li>)}
          <li><SmartLink href="/services" className="inline-flex min-h-11 items-center text-gold">{L('allServices')}</SmartLink></li>
        </ul></div>
        <div><p className="eyebrow mb-5">{L('offices')}</p><ul className="space-y-6 text-sm">
          {site.offices.map(o => <li key={o.key}><p className="font-semibold text-white">{o.country}</p><p className="mt-1 leading-relaxed">{o.address}</p></li>)}
          {site.email && <li><a href={`mailto:${site.email}`} dir="ltr" className="hover:text-gold">{site.email}</a></li>}
          {site.phone && <li><a href={`tel:${site.phone.replace(/\s/g, '')}`} dir="ltr" className="hover:text-gold">{site.phone}</a></li>}
        </ul></div>
      </div>
      <div className="mt-16 flex flex-wrap items-center justify-between gap-4 border-t border-white/[.06] pt-8 text-xs text-white/45">
        <p>© {new Date().getFullYear()} Creative Mark. {L('rights')}</p>
        <p className="max-w-md">{L('mapCredit')} · {L('photoNote')}</p>
        <p lang="en" dir="ltr">Creating The Future</p>
      </div>
    </div>
  </footer>
}

export default function SiteLayout({ children, notice }: { children: ReactNode; notice?: 'machine' | 'missing' | 'review' | null }) {
  const { L, locale } = useSite()
  useSmoothScroll()
  return <>
    <a href="#main" className="skip-link">{L('skip')}</a>
    <Header />
    {notice && <div role="status" data-testid="translation-notice" className="fixed inset-x-0 top-16 xl:top-[88px] z-40 flex items-center justify-center gap-2 bg-gold px-4 py-1.5 text-center text-xs font-medium text-ink">
      <Languages size={13} aria-hidden />{locale.code === 'ar' ? L('pendingAr') : L('pending')}
    </div>}
    <main id="main" tabIndex={-1} className="outline-none">{children}</main>
    <Footer />
  </>
}
