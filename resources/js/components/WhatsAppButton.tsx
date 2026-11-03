import { useEffect, useState } from 'react'
import { useSite } from '@/hooks/useSite'

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
      <svg viewBox="0 0 32 32" className="relative h-7 w-7 md:h-8 md:w-8" fill="currentColor" aria-hidden>
        <path d="M16.04 3C8.86 3 3.03 8.82 3.03 16c0 2.29.6 4.53 1.74 6.5L3 29l6.68-1.75A12.98 12.98 0 0 0 16.04 29C23.2 29 29.03 23.18 29.03 16S23.2 3 16.04 3Zm0 23.62c-2 0-3.95-.54-5.65-1.55l-.4-.24-3.97 1.04 1.06-3.86-.26-.4a10.6 10.6 0 0 1-1.63-5.6c0-5.86 4.78-10.63 10.66-10.63 5.87 0 10.64 4.77 10.64 10.63 0 5.87-4.77 10.61-10.45 10.61Zm5.84-7.95c-.32-.16-1.9-.94-2.19-1.04-.3-.11-.5-.16-.72.16-.21.32-.83 1.04-1.01 1.26-.19.21-.37.24-.69.08-.32-.16-1.35-.5-2.57-1.59-.95-.85-1.59-1.9-1.78-2.22-.18-.32-.02-.49.14-.65.15-.14.32-.37.48-.56.16-.19.21-.32.32-.53.1-.21.05-.4-.03-.56-.08-.16-.72-1.73-.98-2.37-.26-.62-.52-.54-.72-.55h-.61c-.21 0-.56.08-.85.4-.29.32-1.12 1.09-1.12 2.66s1.15 3.09 1.31 3.3c.16.21 2.26 3.45 5.47 4.84.76.33 1.36.53 1.83.68.77.24 1.47.21 2.02.13.62-.09 1.9-.78 2.17-1.53.27-.75.27-1.39.19-1.53-.08-.13-.29-.21-.61-.37Z" />
      </svg>
    </a>
  </div>
}
