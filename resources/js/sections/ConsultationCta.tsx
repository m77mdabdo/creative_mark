import ButtonGroup from '@/components/ButtonGroup'
import { headingMotion } from './shared/motionAttributes'
import { HighlightedTitle } from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/** "Still have questions?" (FR-019), framed in a gold hairline panel as in the deck. */
export default function ConsultationCta(props: SectionProps) {
  const { section } = props
  return <SectionShell {...props}>
    <div className="wrap">
      <div className="mx-auto max-w-4xl rounded-[18px] border border-gold/40 bg-ink/40 px-6 py-14 text-center backdrop-blur-sm md:px-16 md:py-20">
        {section.title && <h2 id={`${section.id}-title`} className="font-display t-display-xl" {...headingMotion(section)}>
          <HighlightedTitle title={section.title} highlight={section.highlight} />
        </h2>}
        {section.body && <p className="t-body-l muted measure mx-auto mt-6">{section.body}</p>}
        <ButtonGroup buttons={section.buttons} className="mt-10 justify-center" />
      </div>
    </div>
  </SectionShell>
}
