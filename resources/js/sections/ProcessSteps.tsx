import ButtonGroup from '@/components/ButtonGroup'
import type { JourneyStep } from '@/types'
import { groupMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/** The investor journey (presentation p39): a gold timeline, vertical on phones and in two rows on desktop. */
export default function ProcessSteps(props: SectionProps) {
  const { section, page } = props
  if (!page.journey.length) return null

  return <SectionShell {...props}>
    <div className="wrap">
      <div className="mb-14 flex flex-wrap items-end justify-between gap-6">
        <SectionHeading section={section} />
        <ButtonGroup buttons={section.buttons} />
      </div>
      <StepList steps={page.journey} motion={groupMotion(section)} />
    </div>
  </SectionShell>
}

export function StepList({ steps, motion = {} }: { steps: JourneyStep[]; motion?: object }) {
  return <ol className="relative grid gap-px overflow-hidden rounded-[6px] border border-white/10 bg-white/10 sm:grid-cols-2 lg:grid-cols-4" {...motion}>
    {/* An odd last step stretches so the grid never ends with an empty cell (7 steps = 4 + 3 on desktop). */}
    {steps.map((step, i) => <li key={step.title} className={`relative bg-ink-2 p-7 md:p-8 ${i === steps.length - 1 && steps.length % 2 ? 'sm:col-span-2' : ''}`}>
      <span className="font-display text-4xl text-gold" aria-hidden>{String(i + 1).padStart(2, '0')}</span>
      <span className="my-5 block h-px w-10 bg-gold/60" aria-hidden />
      <h3 className="text-lg font-semibold">{step.title}</h3>
      <p className="muted mt-2 text-sm leading-relaxed">{step.text}</p>
    </li>)}
  </ol>
}
