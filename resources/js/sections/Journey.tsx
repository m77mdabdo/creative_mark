import ButtonGroup from '@/components/ButtonGroup'
import Media from '@/components/Media'
import TextBlock from '@/components/TextBlock'
import { headingMotion, parallaxMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/** "Your Saudi journey starts here" (FR-012): layered images with parallax beside the story. */
export default function Journey(props: SectionProps) {
  const { section, page } = props
  return <SectionShell {...props}>
    <div className="wrap grid items-center gap-14 lg:grid-cols-2 lg:gap-16">
      <div className="relative h-[420px] md:h-[600px]">
        <div className="absolute inset-y-0 start-0 w-[74%] overflow-hidden rounded-[var(--cm-radius)]">
          <Media media={section.media ? page.media[section.media] : undefined} sizes="(min-width: 1024px) 36vw, 74vw"
            className="absolute -inset-y-[10%] h-[120%] w-full" {...parallaxMotion(section)} />
        </div>
        {section.media2 && <div className="absolute bottom-0 end-0 aspect-[4/5] w-[48%] overflow-hidden rounded-[var(--cm-radius)] border border-gold/40 shadow-[0_30px_80px_-20px_rgba(0,0,0,.8)]">
          <Media media={page.media[section.media2]} sizes="(min-width: 1024px) 24vw, 48vw" className="h-full w-full" />
        </div>}
        <span className="absolute -bottom-4 -start-4 h-24 w-24 border-b border-s border-gold/60" aria-hidden />
      </div>

      <div>
        <SectionHeading section={section} />
        <div className="gold-rule my-8 w-40" data-motion="line" aria-hidden />
        <div {...headingMotion(section)}>
          <TextBlock text={section.body} className="t-body-l muted measure mb-4" />
          <ButtonGroup buttons={section.buttons} className="mt-8" />
        </div>
      </div>
    </div>
  </SectionShell>
}
