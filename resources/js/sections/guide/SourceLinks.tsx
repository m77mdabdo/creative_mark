import { ArrowUpRight } from 'lucide-react'
import { useSite } from '@/hooks/useSite'
import type { SourceRef } from '@/types'
import { formatSourceDate } from './chapters'

/** Numbered references under a chapter: publisher, title and date, opening the original in a new tab. */
export default function SourceLinks({ sources }: { sources: SourceRef[] }) {
  const { t, locale } = useSite()
  if (sources.length === 0) return null

  return <div className="mt-12 border-t border-white/10 pt-6">
    <h3 className="mb-4 text-xs font-semibold uppercase tracking-[.18em] text-white/50">{t('sources')}</h3>
    <ol className="space-y-2.5 text-sm">
      {sources.map((s, i) => <li key={s.id} className="flex gap-3">
        <span className="mt-px w-5 shrink-0 text-gold tabular-nums">{i + 1}</span>
        <a href={s.href} target="_blank" rel="noopener noreferrer" className="group text-white/70 transition hover:text-white">
          {s.publisher && <span className="text-white/90">{s.publisher} · </span>}
          <span className="underline decoration-white/20 underline-offset-4 group-hover:decoration-gold">{s.label}</span>
          {formatSourceDate(s.date, locale.tag) && <span className="text-white/45"> · {formatSourceDate(s.date, locale.tag)}</span>}
          <ArrowUpRight size={13} className="flip-rtl ms-1 inline align-[-1px] text-gold" aria-hidden />
          <span className="sr-only"> ({t('opensNewTab')})</span>
        </a>
      </li>)}
    </ol>
  </div>
}
