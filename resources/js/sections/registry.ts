import type { ComponentType } from 'react'
import type { SectionType } from '@/types'
import ConsultationCta from './ConsultationCta'
import Countries from './Countries'
import FaqPreview from './FaqPreview'
import Hero from './Hero/Hero'
import Journey from './Journey'
import RegionsMapSection from './RegionsMap/RegionsMapSection'
import Sectors from './Sectors'
import Services from './Services'
import type { SectionProps } from './shared/types'
import Testimonials from './Testimonials'
import TrackRecord from './TrackRecord'
import WhyInvest from './WhyInvest'

/**
 * Section type → renderer. The same components render the live site, previews and (later) the builder canvas.
 * Adding a type: create the component, register it here and in App\Content\SectionSchema::TYPES.
 */
export const SECTION_COMPONENTS: Record<SectionType, ComponentType<SectionProps>> = {
  hero: Hero,
  whyInvest: WhyInvest,
  journey: Journey,
  countries: Countries,
  sectors: Sectors,
  trackRecord: TrackRecord,
  services: Services,
  map: RegionsMapSection,
  faq: FaqPreview,
  cta: ConsultationCta,
  testimonials: Testimonials,
}
