import ButtonGroup from '@/components/ButtonGroup'
import Media from '@/components/Media'
import TextBlock from '@/components/TextBlock'
import { headingMotion, parallaxMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/**
 * Photograph beside a story. `layout: split-reverse` puts the image on the inline end.
 * On phones the image comes first at 4:3, on desktop it is a tall 4:5 frame with a gold corner.
 */
export default function ImageText(props: SectionProps) {
  const { section, page } = props
  const reverse = section.layout === 'split-reverse'

  return <SectionShell {...props}>
    <div className="wrap grid items-center gap-10 md:gap-14 lg:grid-cols-2 lg:gap-20">
      <figure className={`relative ${reverse ? 'lg:order-last' : ''}`}>
        <div className="relative aspect-[4/3] overflow-hidden rounded-[var(--cm-radius)] lg:aspect-[4/5]">
          <Media media={section.media ? page.media[section.media] : undefined} sizes="(min-width: 1024px) 45vw, 100vw"
            className="absolute inset-x-0 -inset-y-[8%] h-[116%] w-full" {...parallaxMotion(section, 6)} />
          <div className="absolute inset-0 bg-gradient-to-t from-black/35 via-transparent to-transparent" aria-hidden />
        </div>
        <span className={`absolute -bottom-4 h-20 w-20 border-b border-gold/70 md:-bottom-5 md:h-28 md:w-28 ${reverse ? '-end-4 border-e md:-end-5' : '-start-4 border-s md:-start-5'}`} aria-hidden />
      </figure>

      <div>
        <SectionHeading section={{ ...section, subtitle: null }} />
        <div className="gold-rule my-8 w-32" data-motion="line" aria-hidden />
        <div {...headingMotion(section)}>
          {section.subtitle && <p className="t-body-l accent mb-6 font-medium measure">{section.subtitle}</p>}
          <TextBlock text={section.body} className="muted measure mb-4 leading-relaxed" />
          <ButtonGroup buttons={section.buttons} className="mt-6" />
        </div>
      </div>
    </div>
  </SectionShell>
}
