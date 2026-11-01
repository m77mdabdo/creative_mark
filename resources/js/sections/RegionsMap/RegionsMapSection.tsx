import { lazy, Suspense } from 'react'
import TextBlock from '@/components/TextBlock'
import { headingMotion } from '../shared/motionAttributes'
import SectionHeading from '../shared/SectionHeading'
import SectionShell from '../shared/SectionShell'
import type { SectionProps } from '../shared/types'

/* The boundaries are ~40 KB of path data: loaded only when this section mounts (NFR-01 budget). */
const SaudiMap = lazy(() => import('./SaudiMap'))

/** Map preview of the 13 regions (FR-017). */
export default function RegionsMapSection(props: SectionProps) {
  const { section, page } = props
  return <SectionShell {...props}>
    <div className="wrap">
      <div className="mb-14 grid items-end gap-8 lg:grid-cols-[1.2fr_1fr] lg:gap-10">
        <SectionHeading section={section} />
        <div {...headingMotion(section)}><TextBlock text={section.body} className="muted measure leading-relaxed" /></div>
      </div>
      <Suspense fallback={<div className="aspect-[1000/816] w-full lg:w-[55%]" aria-hidden />}>
        <SaudiMap page={page} />
      </Suspense>
    </div>
  </SectionShell>
}
