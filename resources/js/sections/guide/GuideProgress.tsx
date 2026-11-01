import { useEffect, useState } from 'react'
import { createPortal } from 'react-dom'
import { ChevronDown, ChevronUp } from 'lucide-react'
import { useSite } from '@/hooks/useSite'
import type { Section } from '@/types'
import { chapterNumber } from './chapters'

type State = { visible: boolean; active: number; progress: number }

/**
 * Floating pill shown while the visitor reads the chapters: current chapter, previous/next jumps and a progress line.
 * It reads layout on scroll (passive, once per frame) and hides before the first and after the last chapter.
 */
export default function GuideProgress({ chapters }: { chapters: Section[] }) {
  const { t } = useSite()
  const [state, setState] = useState<State>({ visible: false, active: 0, progress: 0 })

  useEffect(() => {
    let frame = 0
    const measure = () => {
      frame = 0
      const els = chapters.map(c => document.getElementById(c.id)).filter((el): el is HTMLElement => el !== null)
      if (els.length === 0) return
      const vh = window.innerHeight
      const start = els[0].getBoundingClientRect().top
      const end = els[els.length - 1].getBoundingClientRect().bottom
      let active = 0
      els.forEach((el, i) => { if (el.getBoundingClientRect().top < vh * 0.45) active = i })
      const progress = Math.min(1, Math.max(0, (vh * 0.45 - start) / (end - start)))
      setState({ visible: start < vh * 0.45 && end > vh * 0.55, active, progress })
    }
    const onScroll = () => { if (!frame) frame = requestAnimationFrame(measure) }
    measure()
    window.addEventListener('scroll', onScroll, { passive: true })
    window.addEventListener('resize', onScroll)
    return () => { window.removeEventListener('scroll', onScroll); window.removeEventListener('resize', onScroll); cancelAnimationFrame(frame) }
  }, [chapters])

  const go = (i: number) => document.getElementById(chapters[i]?.id ?? '')?.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })
  const current = chapters[state.active]
  if (!current || typeof document === 'undefined') return null

  return createPortal(<nav aria-label={t('readingProgress')}
    className={`fixed inset-x-4 bottom-4 z-40 mx-auto max-w-md overflow-hidden rounded-full border border-gold/30 bg-black/80 shadow-2xl backdrop-blur-md transition duration-500 motion-reduce:transition-none sm:bottom-6 ${state.visible ? 'translate-y-0 opacity-100' : 'pointer-events-none translate-y-6 opacity-0'}`}
    aria-hidden={!state.visible}>
    <div className="flex items-center gap-2 py-1.5 pe-1.5 ps-5 text-white">
      <span dir="ltr" className="font-display text-xl text-gold tabular-nums">{chapterNumber(state.active + 1)}<span className="text-sm text-white/40">/{chapterNumber(chapters.length)}</span></span>
      <span className="min-w-0 flex-1 truncate text-sm" aria-live="polite">{current.eyebrow}</span>
      <button type="button" onClick={() => go(state.active - 1)} disabled={state.active === 0} tabIndex={state.visible ? 0 : -1}
        className="grid h-10 w-10 place-items-center rounded-full transition hover:bg-white/10 disabled:opacity-30" aria-label={t('previousChapter')}><ChevronUp size={18} /></button>
      <button type="button" onClick={() => go(state.active + 1)} disabled={state.active === chapters.length - 1} tabIndex={state.visible ? 0 : -1}
        className="grid h-10 w-10 place-items-center rounded-full bg-gold text-ink transition hover:bg-gold-soft disabled:opacity-30" aria-label={t('nextChapter')}><ChevronDown size={18} /></button>
    </div>
    <div className="h-0.5 bg-white/10" role="progressbar" aria-label={t('readingProgress')} aria-valuemin={0} aria-valuemax={100} aria-valuenow={Math.round(state.progress * 100)}>
      <div className="h-full origin-left bg-gold rtl:origin-right" style={{ transform: `scaleX(${state.progress})` }} />
    </div>
  </nav>, document.body)
}
