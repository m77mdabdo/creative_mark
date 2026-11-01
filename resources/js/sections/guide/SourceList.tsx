import { ArrowUpRight, Info } from 'lucide-react'
import { useSite } from '@/hooks/useSite'
import { groupMotion } from '../shared/motionAttributes'
import SectionHeading from '../shared/SectionHeading'
import SectionShell from '../shared/SectionShell'
import type { SectionProps } from '../shared/types'
import { formatSourceDate } from './chapters'

const host = (href: string) => { try { return new URL(href).hostname.replace(/^www\./, '') } catch { return href } }

/** Library of every publication the guide cites, with the editorial disclaimer. */
export default function SourceList(props: SectionProps) {
  const { section } = props
  const { t, locale } = useSite()

  return <SectionShell {...props}>
    <div className="wrap">
      <SectionHeading section={section} className="mb-12" />
      <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" {...groupMotion(section)}>
        {(section.sources ?? []).map(s => <li key={s.id}>
          <a href={s.href} target="_blank" rel="noopener noreferrer" className="group flex h-full flex-col justify-between gap-5 rounded-[var(--cm-radius)] border border-white/10 p-5 transition hover:border-gold/50 hover:bg-white/[.03]">
            <span>
              <span className="mb-2 block text-xs text-gold">{s.publisher}</span>
              <span className="block leading-snug">{s.label}</span>
            </span>
            <span className="flex items-center justify-between gap-3 text-xs text-white/45">
              <span dir="ltr" className="truncate">{host(s.href)}{formatSourceDate(s.date, locale.tag) ? ` · ${formatSourceDate(s.date, locale.tag)}` : ''}</span>
              <ArrowUpRight size={15} className="flip-rtl shrink-0 text-white/40 transition group-hover:text-gold" aria-hidden />
              <span className="sr-only">{t('opensNewTab')}</span>
            </span>
          </a>
        </li>)}
      </ul>
      {section.note && <p className="mt-12 flex max-w-3xl gap-3 text-sm text-white/55"><Info size={16} className="mt-0.5 shrink-0 text-gold" aria-hidden />{section.note}</p>}
    </div>
  </SectionShell>
}
