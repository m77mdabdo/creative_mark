import type { ComponentType } from 'react'
import type { SectionType } from '@/types'
import CaseGrid from './CaseGrid'
import Comparison from './Comparison'
import ContactInfo from './ContactInfo'
import ConsultationCta from './ConsultationCta'
import Countries from './Countries'
import FaqLibrary from './FaqLibrary'
import FaqPreview from './FaqPreview'
import Checklist from './guide/Checklist'
import GuideChapter from './guide/GuideChapter'
import GuideNav from './guide/GuideNav'
import SourceList from './guide/SourceList'
import Hero from './Hero/Hero'
import NewsGrid from './news/NewsGrid'
import NewsLead from './news/NewsLead'
import ImageText from './ImageText'
import Journey from './Journey'
import LegalText from './LegalText'
import RegionGrid from './RegionGrid'
import RelatedServices from './RelatedServices'
import RegionsMapSection from './RegionsMap/RegionsMapSection'
import Sectors from './Sectors'
import ProcessSteps from './ProcessSteps'
import ServiceGroups from './ServiceGroups'
import ServiceOverview from './ServiceOverview'
import Services from './Services'
import Statement from './Statement'
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
  faqLibrary: FaqLibrary,
  contactInfo: ContactInfo,
  legalText: LegalText,
  serviceOverview: ServiceOverview,
  relatedServices: RelatedServices,
  statement: Statement,
  imageText: ImageText,
  cards: WhyInvest,
  serviceGroups: ServiceGroups,
  processSteps: ProcessSteps,
  comparison: Comparison,
  regionGrid: RegionGrid,
  guideNav: GuideNav,
  guideChapter: GuideChapter,
  checklist: Checklist,
  sourceList: SourceList,
  newsLead: NewsLead,
  newsGrid: NewsGrid,
  caseGrid: CaseGrid,
}
