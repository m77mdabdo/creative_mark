import { useMemo } from 'react'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/**
 * Long-form legal text (privacy, terms). The body is plain text: lines starting with "## " become headings,
 * other lines paragraphs. A table of contents links to each heading; CMS text is never treated as HTML.
 */
export default function LegalText(props: SectionProps) {
  const { section } = props
  const blocks = useMemo(() => {
    const out: { heading: string; id: string; paragraphs: string[] }[] = []
    for (const line of (section.body ?? '').split('\n').map(l => l.trim()).filter(Boolean)) {
      if (line.startsWith('## ')) out.push({ heading: line.slice(3), id: `${section.id}-${out.length + 1}`, paragraphs: [] })
      else (out.at(-1) ?? out[out.push({ heading: '', id: `${section.id}-0`, paragraphs: [] }) - 1]).paragraphs.push(line)
    }
    return out
  }, [section.body, section.id])

  return <SectionShell {...props}>
    <div className="wrap grid gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,2.4fr)] lg:gap-20">
      <nav className="lg:sticky lg:top-28 lg:self-start">
        <ol className="space-y-1 border-s border-current/15 text-sm">
          {blocks.filter(b => b.heading).map((b, i) => <li key={b.id}>
            <a href={`#${b.id}`} className="-ms-px flex min-h-10 items-center gap-3 border-s border-transparent ps-4 opacity-70 transition hover:border-gold hover:opacity-100">
              <span className="tabular-nums text-gold">{String(i + 1).padStart(2, '0')}</span>{b.heading}
            </a>
          </li>)}
        </ol>
      </nav>
      <article className="measure">
        {blocks.map((b, i) => <section key={b.id} id={b.id} className="scroll-mt-28 border-b border-current/10 py-8 first:pt-0 last:border-0">
          {b.heading && <h2 className="font-display mb-4 flex items-baseline gap-4 text-2xl md:text-3xl">
            <span className="text-base tabular-nums text-gold" aria-hidden>{String(i + 1).padStart(2, '0')}</span>{b.heading}
          </h2>}
          {b.paragraphs.map((p, j) => <p key={j} className="mb-4 leading-relaxed opacity-80 last:mb-0">{p}</p>)}
        </section>)}
        {section.note && <p className="mt-10 text-sm opacity-60">{section.note}</p>}
      </article>
    </div>
  </SectionShell>
}
