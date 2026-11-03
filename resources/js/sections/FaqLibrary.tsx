import { useId, useMemo, useState } from 'react'
import { Minus, Plus, Search } from 'lucide-react'
import { useSite } from '@/hooks/useSite'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/**
 * Every published question, filtered by category chips and a search box. Accordion keeps one answer open;
 * the search matches questions and answers in the visitor's language.
 */
export default function FaqLibrary(props: SectionProps) {
  const { page } = props
  const { t } = useSite()
  const library = page.faqLibrary ?? { categories: [], items: [] }
  const [category, setCategory] = useState<string | null>(null)
  const [query, setQuery] = useState('')
  const [openId, setOpenId] = useState<number | null>(library.items[0]?.id ?? null)
  const uid = useId()

  const items = useMemo(() => {
    const q = query.trim().toLocaleLowerCase()
    return library.items.filter(f => (!category || f.category === category)
      && (!q || `${f.question} ${f.answer}`.toLocaleLowerCase().includes(q)))
  }, [library.items, category, query])

  const chip = (on: boolean) => `inline-flex min-h-11 items-center rounded-full border px-5 text-sm transition ${on ? 'border-gold bg-gold text-ink' : 'border-white/15 text-white/75 hover:border-white/40'}`
  const count = (key: string | null) => library.items.filter(f => !key || f.category === key).length

  return <SectionShell {...props}>
    <div className="wrap grid gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,2.2fr)] lg:gap-16">
      <aside className="lg:sticky lg:top-28 lg:self-start">
        <label className="relative block">
          <span className="sr-only">{t('searchFaq')}</span>
          <Search size={18} className="pointer-events-none absolute start-4 top-1/2 -translate-y-1/2 text-white/40" aria-hidden />
          <input type="search" value={query} onChange={e => setQuery(e.target.value)} placeholder={t('searchFaq')}
            className="block w-full rounded-[var(--cm-radius)] border border-white/15 bg-black/40 py-3.5 pe-4 ps-12 text-white placeholder:text-white/35 focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold" />
        </label>
        <div className="mt-6 flex flex-wrap gap-2 lg:flex-col lg:items-start" role="group" aria-label={t('faqTopics')}>
          <button type="button" aria-pressed={category === null} onClick={() => setCategory(null)} className={chip(category === null)}>
            {t('allTopics')}<span className="ms-2 tabular-nums opacity-60">{count(null)}</span>
          </button>
          {library.categories.map(c => <button key={c.key} type="button" aria-pressed={category === c.key} onClick={() => setCategory(c.key)} className={chip(category === c.key)}>
            {c.label}<span className="ms-2 tabular-nums opacity-60">{count(c.key)}</span>
          </button>)}
        </div>
      </aside>

      <div>
        <p className="mb-5 text-sm text-white/50" aria-live="polite">{items.length} / {library.items.length}</p>
        {items.length === 0 && <p className="rounded-[var(--cm-radius)] border border-white/10 p-8 text-center text-white/60">{t('noFaqResults')}</p>}
        <div className="space-y-3">
          {items.map((faq, i) => {
            const open = openId === faq.id
            const questionId = `${uid}-q${faq.id}`
            const answerId = `${uid}-a${faq.id}`
            return <div key={faq.id} className={`rounded-[var(--cm-radius)] border transition-colors ${open ? 'border-gold/60 bg-white/[.03]' : 'border-white/12 hover:border-gold/40'}`}>
              <h2>
                <button type="button" id={questionId} aria-expanded={open} aria-controls={answerId} onClick={() => setOpenId(open ? null : faq.id)}
                  className="flex min-h-16 w-full items-center gap-5 px-5 py-5 text-start text-lg font-medium md:px-6">
                  <span className="font-display w-8 shrink-0 text-gold/70 tabular-nums" aria-hidden>{String(i + 1).padStart(2, '0')}</span>
                  <span className="flex-1">{faq.question}</span>
                  <span className={`grid h-9 w-9 shrink-0 place-items-center rounded-full border transition ${open ? 'border-gold bg-gold text-ink' : 'border-white/25'}`} aria-hidden>
                    {open ? <Minus size={14} /> : <Plus size={14} />}
                  </span>
                </button>
              </h2>
              <div id={answerId} role="region" aria-labelledby={questionId} hidden={!open} className="pb-6 pe-6 ps-[4.25rem] md:ps-[4.75rem]">
                <p className="muted measure leading-relaxed">{faq.answer}</p>
              </div>
            </div>
          })}
        </div>
      </div>
    </div>
  </SectionShell>
}
