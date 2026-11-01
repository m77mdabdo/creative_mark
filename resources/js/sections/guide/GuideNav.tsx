import { ArrowDown } from 'lucide-react'
import { useSite } from '@/hooks/useSite'
import { groupMotion } from '../shared/motionAttributes'
import SectionHeading from '../shared/SectionHeading'
import SectionShell from '../shared/SectionShell'
import type { SectionProps } from '../shared/types'
import { chapterNumber, guideChapters } from './chapters'
import GuideProgress from './GuideProgress'

/** Table of contents of the investor guide plus the floating reading-progress bar. */
export default function GuideNav(props: SectionProps) {
  const { section, page } = props
  const { t } = useSite()
  const chapters = guideChapters(page)

  return <SectionShell {...props}>
    <div className="wrap">
      <SectionHeading section={section} className="mb-12" />
      <ol className="grid gap-px overflow-hidden rounded-[var(--cm-radius)] border border-white/10 bg-white/10 sm:grid-cols-2 lg:grid-cols-4" {...groupMotion(section)}>
        {chapters.map((c, i) => <li key={c.id} className="bg-charcoal">
          <a href={`#${c.id}`} className="group flex h-full min-h-[150px] flex-col justify-between gap-6 p-6 transition hover:bg-white/[.04] md:min-h-[180px]">
            <span className="flex items-center justify-between">
              <span className="font-display text-4xl text-transparent transition [-webkit-text-stroke:1px_rgba(235,189,108,.8)] group-hover:text-gold">{chapterNumber(i + 1)}</span>
              <ArrowDown size={16} className="text-white/30 transition group-hover:translate-y-1 group-hover:text-gold" aria-hidden />
            </span>
            <span>
              <span className="mb-1 block text-xs text-gold">{t('chapter')} {chapterNumber(i + 1)} · {c.eyebrow}</span>
              <span className="block text-lg leading-snug">{c.title}</span>
            </span>
          </a>
        </li>)}
      </ol>
    </div>
    <GuideProgress chapters={chapters} />
  </SectionShell>
}
