import TextBlock from '@/components/TextBlock'
import FeatureCardGrid from './shared/FeatureCardGrid'
import { headingMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/** "Why invest in Saudi Arabia" (FR-011). Cards quoting unverified figures never arrive here (RULE-01). */
export default function WhyInvest(props: SectionProps) {
  const { section } = props
  return <SectionShell {...props}>
    <div className="wrap">
      <div className="mb-14 grid items-end gap-8 lg:mb-16 lg:grid-cols-[1.2fr_1fr] lg:gap-10">
        <SectionHeading section={section} />
        <div {...headingMotion(section)}><TextBlock text={section.body} className="muted measure leading-relaxed" /></div>
      </div>
      <FeatureCardGrid section={section} />
    </div>
  </SectionShell>
}
