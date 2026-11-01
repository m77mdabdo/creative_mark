import Media from '@/components/Media'
import TextBlock from '@/components/TextBlock'
import { groupMotion, headingMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/** "Our sectors" (FR-014): image tiles whose description reveals on hover or focus (always visible on touch). */
export default function Sectors(props: SectionProps) {
  const { section, page } = props
  const reveal = 'lg:max-h-0 lg:overflow-hidden lg:opacity-0 lg:transition-all lg:duration-500 lg:group-hover:max-h-24 lg:group-hover:opacity-100 lg:group-focus-within:max-h-24 lg:group-focus-within:opacity-100'

  return <SectionShell {...props}>
    <div className="wrap">
      <div className="mb-14 grid items-end gap-8 lg:grid-cols-[1.2fr_1fr] lg:gap-10">
        <SectionHeading section={section} />
        <div {...headingMotion(section)}><TextBlock text={section.body} className="muted measure leading-relaxed" /></div>
      </div>
      <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" {...groupMotion(section)}>
        {section.cards.map((card, i) => <li key={card.id} tabIndex={0} className="group relative h-[300px] overflow-hidden rounded-[var(--cm-radius)] md:h-[360px]">
          <Media media={card.media ? page.media[card.media] : undefined} sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
            className="absolute inset-0 h-full w-full transition duration-[1.1s] ease-[var(--ease-out)] group-hover:scale-[1.06]" />
          <div className="absolute inset-0 bg-gradient-to-t from-black/90 via-black/35 to-black/5" aria-hidden />
          <div className="absolute inset-x-0 bottom-0 p-7 text-white">
            <span className="font-display text-xl text-gold" aria-hidden>{String(i + 1).padStart(2, '0')}</span>
            <h3 className="mt-2 text-xl font-semibold">{card.title}</h3>
            <p className={`mt-2 text-sm text-white/75 ${reveal}`}>{card.text}</p>
          </div>
        </li>)}
      </ul>
    </div>
  </SectionShell>
}
