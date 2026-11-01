import type { Section } from '@/types'
import { headingMotion } from './motionAttributes'

type Props = { section: Section; center?: boolean; className?: string; as?: 'h1' | 'h2' }

/** Eyebrow, title (with optional gold highlight) and subtitle, in the type scale of Phase C/D §11. */
export default function SectionHeading({ section, center, className = '', as: Tag = 'h2' }: Props) {
  const centered = center ?? section.style.align === 'center'

  return <div className={`flex max-w-3xl flex-col ${centered ? 'mx-auto items-center text-center' : ''} ${className}`} {...headingMotion(section)}>
    {section.eyebrow && <p className="eyebrow mb-5 flex items-center gap-3">
      {!centered && <span className="h-px w-8 bg-current" aria-hidden />}{section.eyebrow}
    </p>}
    {section.title && <Tag id={`${section.id}-title`} className="font-display t-display-l">
      <HighlightedTitle title={section.title} highlight={section.highlight} />
    </Tag>}
    {section.subtitle && <p className="t-body-l muted measure mt-5">{section.subtitle}</p>}
  </div>
}

/** Shows the editor-chosen part of the title in gold; falls back to the plain title if it is not found. */
export function HighlightedTitle({ title, highlight }: { title: string; highlight?: string | null }) {
  const at = highlight ? title.indexOf(highlight) : -1
  if (!highlight || at < 0) return <>{title}</>

  return <>
    {title.slice(0, at)}
    <span className="accent">{highlight}</span>
    {title.slice(at + highlight.length)}
  </>
}
