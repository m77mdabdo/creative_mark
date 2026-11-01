import Flag from '@/components/Flag'
import Logo from '@/components/Logo'
import TextBlock from '@/components/TextBlock'
import { groupMotion, headingMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/** "Countries we work with" (FR-013): 14 records shown as round flags in a framed panel, as in the deck. */
export default function Countries(props: SectionProps) {
  const { section, page } = props
  return <SectionShell {...props}>
    <div className="wrap grid items-center gap-14 lg:grid-cols-[1.05fr_1fr] lg:gap-20">
      <div className="relative rounded-[18px] border border-gold/30 bg-gradient-to-b from-[#16130d] to-[#0b0b0c] p-8 shadow-[0_40px_120px_-40px_rgba(235,189,108,.25)] md:p-10">
        <div className="mb-8 flex flex-col items-center text-center">
          <Logo className="h-12" />
          <span className="mt-6 h-px w-24 bg-gradient-to-r from-transparent via-gold to-transparent" aria-hidden />
        </div>
        <ul className="grid grid-cols-3 gap-x-3 gap-y-6 sm:grid-cols-4 lg:grid-cols-5" {...groupMotion(section)}>
          {page.countries.map(country => <li key={country.code} className="flex flex-col items-center gap-2 text-center">
            <Flag code={country.code} size={44} />
            <span className="text-[11px] leading-tight text-white/75">{country.name}</span>
          </li>)}
        </ul>
      </div>

      <div className="order-first lg:order-none">
        <SectionHeading section={section} />
        <div {...headingMotion(section)}><TextBlock text={section.body} className="muted measure mt-8 leading-relaxed" /></div>
      </div>
    </div>
  </SectionShell>
}
