import { useEffect, useState } from 'react'
import { useSite } from '@/hooks/useSite'
import WhatsAppGlyph from './WhatsAppGlyph'

/**
 * Floating WhatsApp button (number from the site's contact settings). It slides in after the visitor starts
 * scrolling, shows a short invitation once, and opens WhatsApp with a greeting in the visitor's language.
 * Hidden on the booking pages, where it would compete with the form.
 */
export default function WhatsAppButton({ hidden = false }: { hidden?: boolean }) {
  const { site, t } = useSite()
  const [visible, setVisible] = useState(false)
  const [hint, setHint] = useState(false)
  const digits = (site.whatsapp ?? '').replace(/\D/g, '')

  useEffect(() => {
    const onScroll = () => setVisible(window.scrollY > 240)
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    let seen = false
    try { seen = sessionStorage.getItem('cm-wa-hint') === '1' } catch { /* storage blocked */ }
    const timer = seen ? undefined : window.setTimeout(() => {
      setHint(true)
      try { sessionStorage.setItem('cm-wa-hint', '1') } catch { /* storage blocked */ }
      window.setTimeout(() => setHint(false), 6000)
    }, 9000)
    return () => { window.removeEventListener('scroll', onScroll); window.clearTimeout(timer) }
  }, [])

  if (!digits || hidden) return null
  const href = `https://wa.me/${digits}?text=${encodeURIComponent(t('whatsappGreeting'))}`

  return <div className={`fixed bottom-5 end-5 z-50 flex items-center gap-3 transition-all duration-500 motion-reduce:transition-none md:bottom-8 md:end-8 ${visible ? 'translate-y-0 opacity-100' : 'pointer-events-none translate-y-6 opacity-0'}`}>
    <div role="status" aria-live="polite" className={`hidden max-w-[15rem] rounded-2xl rounded-ee-sm bg-white px-4 py-3 text-sm leading-snug text-ink shadow-2xl transition-all duration-500 motion-reduce:transition-none sm:block ${hint ? 'translate-x-0 opacity-100' : 'pointer-events-none translate-x-3 opacity-0 rtl:-translate-x-3'}`}>
      <strong className="block text-[13px]">Creative Mark</strong>{t('whatsappHint')}
    </div>
    <a href={href} target="_blank" rel="noopener noreferrer" aria-label={t('whatsappLabel')} title={t('whatsappLabel')}
      onMouseEnter={() => setHint(true)} onMouseLeave={() => setHint(false)} onFocus={() => setHint(true)} onBlur={() => setHint(false)}
      className="group relative grid h-14 w-14 place-items-center rounded-full bg-[#25D366] text-white shadow-[0_12px_32px_-8px_rgba(37,211,102,.65)] ring-2 ring-gold/70 ring-offset-2 ring-offset-black/0 transition hover:scale-105 focus-visible:outline-none focus-visible:ring-4 md:h-16 md:w-16">
      <span className="absolute inset-0 animate-ping rounded-full bg-[#25D366]/40 motion-reduce:hidden [animation-duration:2.4s]" aria-hidden />
      <WhatsAppGlyph className="relative h-7 w-7 md:h-8 md:w-8" />
    </a>
  </div>
}
