import { useEffect, useRef, useState } from 'react'
import { Globe } from 'lucide-react'
import { useSite } from '@/hooks/useSite'

/** Lists each language in its own script (no flags: languages are not countries) and keeps the current page. */
export default function LanguageSwitcher() {
  const { locale } = useSite()
  const [open, setOpen] = useState(false)
  const root = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (!open) return
    const close = (e: MouseEvent | KeyboardEvent) => {
      const outside = e instanceof MouseEvent && !root.current?.contains(e.target as Node)
      if (outside || (e instanceof KeyboardEvent && e.key === 'Escape')) setOpen(false)
    }
    document.addEventListener('mousedown', close)
    document.addEventListener('keydown', close)
    return () => {
      document.removeEventListener('mousedown', close)
      document.removeEventListener('keydown', close)
    }
  }, [open])

  const pathWithoutLocale = typeof window === 'undefined' ? '' : window.location.pathname.replace(/^\/[a-z]{2}(?=\/|$)/, '')

  return <div ref={root} className="relative">
    <button type="button" onClick={() => setOpen(!open)} aria-expanded={open} aria-haspopup="true" data-testid="lang-switch"
      className="flex h-11 items-center gap-1.5 rounded-full border border-white/20 px-3.5 text-xs font-semibold uppercase transition hover:border-gold">
      <Globe size={14} aria-hidden /><span lang={locale.tag}>{locale.code}</span>
    </button>

    {open && <ul className="absolute end-0 top-13 z-60 w-48 overflow-hidden rounded-md border border-white/10 bg-[#111]/98 py-1 shadow-2xl backdrop-blur">
      {locale.available.map(l => <li key={l.code}>
        {/* full page load: the whole document changes language and direction */}
        <a href={`/${l.code}${pathWithoutLocale}`} lang={l.tag} hrefLang={l.tag} aria-current={l.code === locale.code ? 'true' : undefined}
          className={`flex min-h-11 items-center justify-between px-4 text-sm hover:bg-white/5 ${l.code === locale.code ? 'text-gold' : 'text-white/85'}`}>
          <span>{l.native}</span><span className="text-[10px] uppercase text-white/40">{l.code}</span>
        </a>
      </li>)}
    </ul>}
  </div>
}
