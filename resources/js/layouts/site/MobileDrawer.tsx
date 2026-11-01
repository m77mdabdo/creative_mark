import { useEffect, useRef } from 'react'
import { X } from 'lucide-react'
import Icon from '@/components/Icon'
import Logo from '@/components/Logo'
import SmartLink from '@/components/SmartLink'
import { useSite } from '@/hooks/useSite'

/** Full-height menu for < 1280 px: focus trapped, Esc closes, body scroll locked, background inert. */
export default function MobileDrawer({ onClose }: { onClose: () => void }) {
  const { site, t } = useSite()
  const panel = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const el = panel.current!
    const background = [document.getElementById('main'), document.querySelector('footer')]
    background.forEach(node => node?.setAttribute('inert', ''))
    document.body.style.overflow = 'hidden'

    const focusables = () => Array.from(el.querySelectorAll<HTMLElement>('a[href],button:not([disabled])'))
    focusables()[0]?.focus()

    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose()
      if (e.key !== 'Tab') return
      const items = focusables()
      const first = items[0]
      const last = items[items.length - 1]
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus() }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus() }
    }
    document.addEventListener('keydown', onKey)

    return () => {
      document.removeEventListener('keydown', onKey)
      document.body.style.overflow = ''
      background.forEach(node => node?.removeAttribute('inert'))
      document.querySelector<HTMLElement>('[data-testid="menu-button"]')?.focus()
    }
  }, [onClose])

  return <div ref={panel} className="fixed inset-0 z-60 overflow-y-auto bg-ink text-white" role="dialog" aria-modal="true" aria-label={t('menu')}>
    <div className="wrap flex h-16 items-center justify-between">
      <Logo className="h-9" />
      <button type="button" className="grid h-11 w-11 place-items-center" onClick={onClose} aria-label={t('close')}><X aria-hidden /></button>
    </div>

    <nav className="wrap pb-16" aria-label={t('mainNav')}>
      {site.nav.map(item => <div key={item.href} className="border-b border-white/10">
        <SmartLink href={item.href} className="flex min-h-14 items-center font-display text-3xl">{item.label}</SmartLink>
        {item.mega === 'services' && <ul className="grid gap-1 pb-4">
          {site.megaServices.map(service => <li key={service.slug}>
            <SmartLink href={`/services/${service.slug}`} className="flex min-h-11 items-center gap-3 text-sm text-white/65">
              <Icon name={service.icon} size={15} className="text-gold" />{service.title}
            </SmartLink>
          </li>)}
        </ul>}
      </div>)}
      {site.cta && <SmartLink href={site.cta.href} className="btn btn-gold mt-8 w-full">{site.cta.label}</SmartLink>}
    </nav>
  </div>
}
