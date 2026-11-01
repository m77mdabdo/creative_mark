import { useEffect, useState } from 'react'
import { Check, RotateCcw } from 'lucide-react'
import { useSite } from '@/hooks/useSite'
import { groupMotion } from '../shared/motionAttributes'
import SectionHeading from '../shared/SectionHeading'
import SectionShell from '../shared/SectionShell'
import type { SectionProps } from '../shared/types'

const storageKey = (sectionId: string) => `cm-checklist-${sectionId}`

function load(sectionId: string): string[] {
  try {
    const raw = window.localStorage.getItem(storageKey(sectionId))
    const value: unknown = raw ? JSON.parse(raw) : []
    return Array.isArray(value) ? value.filter((v): v is string => typeof v === 'string') : []
  } catch {
    return [] // private mode or blocked storage: the checklist still works for this visit
  }
}

/** Interactive checklist: each card is a step; ticks are kept in this browser only (no personal data leaves it). */
export default function Checklist(props: SectionProps) {
  const { section } = props
  const { t } = useSite()
  const [done, setDone] = useState<string[]>([])
  useEffect(() => setDone(load(section.id)), [section.id])

  const save = (next: string[]) => {
    setDone(next)
    try { window.localStorage.setItem(storageKey(section.id), JSON.stringify(next)) } catch { /* not persisted */ }
  }
  const toggle = (id: string) => save(done.includes(id) ? done.filter(d => d !== id) : [...done, id])
  const total = section.cards.length
  const count = section.cards.filter(c => done.includes(c.id)).length

  return <SectionShell {...props}>
    <div className="wrap">
      <div className="mb-12 flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
        <SectionHeading section={section} />
        <div className="w-full max-w-sm shrink-0">
          <p className="mb-3 flex items-baseline justify-between gap-4 text-sm" aria-live="polite">
            <span><span className="font-display text-3xl text-gold tabular-nums">{count}</span> <span className="text-white/60">{t('of')} {total} {t('completed')}</span></span>
            {count > 0 && <button type="button" onClick={() => save([])} className="inline-flex min-h-11 items-center gap-1.5 text-white/60 transition hover:text-gold">
              <RotateCcw size={14} aria-hidden />{t('resetChecklist')}
            </button>}
          </p>
          <div className="h-1 overflow-hidden rounded-full bg-white/10" aria-hidden>
            <div className="h-full origin-left bg-gold transition-transform duration-500 rtl:origin-right" style={{ transform: `scaleX(${total ? count / total : 0})` }} />
          </div>
        </div>
      </div>
      <ol className="grid gap-3 md:grid-cols-2" {...groupMotion(section)}>
        {section.cards.map((c, i) => {
          const checked = done.includes(c.id)
          return <li key={c.id}>
            <label className={`flex h-full cursor-pointer items-start gap-4 rounded-[var(--cm-radius)] border p-5 transition md:p-6 ${checked ? 'border-gold/50 bg-gold/[.08]' : 'border-white/10 bg-black/40 hover:border-white/25'}`}>
              <input type="checkbox" className="peer sr-only" checked={checked} onChange={() => toggle(c.id)} />
              <span className={`mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full border transition peer-focus-visible:ring-2 peer-focus-visible:ring-gold peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-ink ${checked ? 'border-gold bg-gold text-ink' : 'border-white/30 text-transparent'}`} aria-hidden>
                <Check size={15} strokeWidth={2.5} />
              </span>
              <span className="min-w-0">
                <span className="mb-1 block text-xs text-gold tabular-nums">{String(i + 1).padStart(2, '0')}</span>
                <span className={`block text-lg font-medium transition ${checked ? 'text-white/60 line-through decoration-gold/60' : ''}`}>{c.title}</span>
                <span className="mt-1 block text-sm text-white/60">{c.text}</span>
              </span>
            </label>
          </li>
        })}
      </ol>
    </div>
  </SectionShell>
}
