import { useCallback, useEffect, useRef, useState } from 'react'
import { Link, router } from '@inertiajs/react'
import { ChevronDown, Menu, MessageSquareText } from 'lucide-react'
import Logo from '@/components/Logo'
import SmartLink from '@/components/SmartLink'
import { useSite } from '@/hooks/useSite'
import LanguageSwitcher from './LanguageSwitcher'
import MegaMenu from './MegaMenu'
import MobileDrawer from './MobileDrawer'

const SOLID_AFTER = 80 // px scrolled before the header gets its ink background
const HIDE_AFTER = 400 // px scrolled before a fast downward scroll may hide it
const MEGA_OPEN_DELAY = 150 // hover intent, ms

/** Hides on fast downward scroll and returns on any upward scroll, never while focus is inside. */
function useHeaderScroll(header: React.RefObject<HTMLElement | null>) {
  const [solid, setSolid] = useState(false)
  const [hidden, setHidden] = useState(false)

  useEffect(() => {
    let last = window.scrollY
    const onScroll = () => {
      const y = window.scrollY
      setSolid(y > SOLID_AFTER)
      if (!header.current?.contains(document.activeElement)) {
        if (y > HIDE_AFTER && y - last > 12) setHidden(true)
        else if (y < last) setHidden(false)
      }
      last = y
    }
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [header])

  return { solid, hidden }
}

export default function Header() {
  const { site, t, href } = useSite()
  const header = useRef<HTMLElement>(null)
  const { solid, hidden } = useHeaderScroll(header)
  const [drawerOpen, setDrawerOpen] = useState(false)
  const [megaOpen, setMegaOpen] = useState(false)
  const megaTimer = useRef<number | undefined>(undefined)

  useEffect(() => router.on('navigate', () => { setDrawerOpen(false); setMegaOpen(false) }), [])

  const closeDrawer = useCallback(() => setDrawerOpen(false), [])
  const scheduleMega = (open: boolean) => {
    window.clearTimeout(megaTimer.current)
    megaTimer.current = window.setTimeout(() => setMegaOpen(open), open ? MEGA_OPEN_DELAY : 120)
  }
  const onKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Escape' && megaOpen) {
      setMegaOpen(false)
      header.current?.querySelector<HTMLElement>('[data-mega-trigger]')?.focus()
    }
  }

  const navLink = 'flex min-h-11 items-center px-3 text-[13px] font-medium text-white/85 transition hover:text-gold'

  return <>
    <header ref={header} onKeyDown={onKeyDown}
      className={`fixed inset-x-0 top-0 z-50 text-white transition-[transform,background-color] duration-500 ${hidden && !drawerOpen ? '-translate-y-full' : ''} ${solid ? 'border-b border-white/[.06] bg-ink/92 backdrop-blur-xl' : 'bg-gradient-to-b from-black/60 to-transparent'}`}>
      <div className={`wrap flex items-center gap-4 transition-[height] duration-500 ${solid ? 'h-16' : 'h-16 xl:h-[88px]'}`}>
        <Link href={href('/')} aria-label={t('home')} className="shrink-0"><Logo className="h-9 xl:h-11" /></Link>

        <nav className="ms-auto hidden items-center xl:flex" aria-label={t('mainNav')}>
          {site.nav.map(item => item.mega === 'services'
            ? <div key={item.href} onMouseEnter={() => scheduleMega(true)} onMouseLeave={() => scheduleMega(false)}>
                <button type="button" data-mega-trigger aria-expanded={megaOpen} aria-controls="mega-services" onClick={() => setMegaOpen(!megaOpen)} className={`${navLink} gap-1`}>
                  {item.label}<ChevronDown size={13} className={`transition ${megaOpen ? 'rotate-180' : ''}`} aria-hidden />
                </button>
              </div>
            : <SmartLink key={item.href} href={item.href} className={navLink}>{item.label}</SmartLink>)}
        </nav>

        <div className="ms-auto flex items-center gap-2 xl:ms-3">
          <LanguageSwitcher />
          {site.cta && <>
            <SmartLink href={site.cta.href} className="btn btn-gold hidden !min-h-11 !px-4 !py-2 !text-[11px] sm:inline-flex">{site.cta.label}</SmartLink>
            <SmartLink href={site.cta.href} aria-label={site.cta.label} className="grid h-11 w-11 place-items-center rounded-[var(--cm-radius)] bg-gold text-ink sm:hidden"><MessageSquareText size={18} aria-hidden /></SmartLink>
          </>}
          <button type="button" data-testid="menu-button" onClick={() => setDrawerOpen(true)} aria-label={t('menu')} aria-expanded={drawerOpen} className="grid h-11 w-11 place-items-center xl:hidden"><Menu aria-hidden /></button>
        </div>
      </div>

      {megaOpen && <MegaMenu id="mega-services" onMouseEnter={() => scheduleMega(true)} onMouseLeave={() => scheduleMega(false)} />}
    </header>

    {drawerOpen && <MobileDrawer onClose={closeDrawer} />}
  </>
}
