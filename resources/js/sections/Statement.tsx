import { headingMotion } from './shared/motionAttributes'
import { HighlightedTitle } from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/** A single positioning statement set large between two gold hairlines. */
export default function Statement(props: SectionProps) {
  const { section } = props
  return <SectionShell {...props}>
    <div className="wrap">
      <div className="mx-auto max-w-5xl text-center">
        {section.eyebrow && <p className="eyebrow mb-8">{section.eyebrow}</p>}
        {section.subtitle && <p className="t-body-l muted mx-auto max-w-2xl">{section.subtitle}</p>}
        <span className="mx-auto my-10 block h-px w-24 bg-gradient-to-r from-transparent via-gold to-transparent" data-motion="line" aria-hidden />
        {section.title && <h2 id={`${section.id}-title`} className="font-display t-display-l" {...headingMotion(section)}>
          <HighlightedTitle title={section.title} highlight={section.highlight} />
        </h2>}
        <span className="mx-auto mt-10 block h-px w-24 bg-gradient-to-r from-transparent via-gold to-transparent" data-motion="line" aria-hidden />
      </div>
    </div>
  </SectionShell>
}
