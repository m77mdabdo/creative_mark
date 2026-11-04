/**
 * Page content contract. Mirrors what App\Content\PageData sends: every string is already
 * resolved to the visitor's locale (with English fallback) and filtered by the content rules.
 */

export type ButtonStyle = 'gold' | 'outline' | 'link'
export type Button = { label: string; href: string; style: ButtonStyle }

export type Card = { id: string; title: string | null; text: string | null; icon?: string; media?: string }

export type MotionPreset = 'none' | 'fade-up' | 'reveal-mask' | 'zoom-in' | 'parallax-slow' | 'stagger-cards' | 'counter' | 'line-draw'

export type SectionType =
  | 'hero' | 'whyInvest' | 'journey' | 'countries' | 'sectors' | 'trackRecord'
  | 'services' | 'map' | 'faq' | 'cta' | 'testimonials'
  | 'statement' | 'imageText' | 'cards' | 'serviceGroups' | 'processSteps' | 'comparison' | 'regionGrid'
  | 'guideNav' | 'guideChapter' | 'checklist' | 'sourceList' | 'newsLead' | 'newsGrid' | 'caseGrid'
  | 'faqLibrary' | 'contactInfo' | 'legalText' | 'serviceOverview' | 'relatedServices' | 'regionOverview' | 'moreRegions'

/** One frame of the hero slideshow (copy already resolved to the visitor's locale). */
export type Slide = {
  id: string; media: string; eyebrow?: string | null; title?: string | null; highlight?: string | null; subtitle?: string | null
  /** Region slug whose investment opportunities are listed under the copy. */
  region?: string
}

/** A cited reference (investor guide). `date` is YYYY, YYYY-MM or YYYY-MM-DD. */
export type SourceRef = { id: string; label: string; publisher: string | null; href: string; date?: string }

export type SectionTheme = 'dark' | 'charcoal' | 'ivory' | 'white'

export type Section = {
  id: string
  type: SectionType
  eyebrow?: string | null
  title?: string | null
  /** Part of the title shown in gold (e.g. "One place,"). */
  highlight?: string | null
  subtitle?: string | null
  body?: string | null
  media?: string
  media2?: string
  limit?: number
  layout?: 'split' | 'split-reverse'
  buttons: Button[]
  cards: Card[]
  slides: Slide[]
  /** One item per line (guide chapters). */
  points?: string | null
  /** Short callout shown beside the body. */
  note?: string | null
  sources?: SourceRef[]
  style: { theme: SectionTheme; overlay: number; padding: 'sm' | 'md' | 'lg'; align: 'start' | 'center'; bgMedia?: string }
  motion: { preset: MotionPreset; intensity: number; delay: number }
}

export type MediaItem = { src: string; srcset: string; alt: string }

export type ServiceGroup = 'setup' | 'grow' | 'comply'
export type ServiceItem = { slug: string; icon: string; media: string; order: number; group: ServiceGroup; title: string; tagline: string; summary: string; cta: string }
export type JourneyStep = { title: string; text: string }

export type RegionItem = {
  slug: string; media: string; name: string; capital: string; bestFor: string
  description: string; opportunities: string[]; industries: string[]; services: string[]
}

export type NewsCategory = 'economy' | 'regulation' | 'real-estate' | 'tourism' | 'tax' | 'zones'

/** A newsroom story (App\Content\PageData::articles). `date` is the original report's date (YYYY-MM-DD). */
export type NewsItem = {
  slug: string; category: NewsCategory; media: string; featured: boolean; date: string
  source: { name: string; url: string }; title: string; excerpt: string
}

export type NewsArticle = NewsItem & { body: string; takeaway: string | null; facts: { value: string; label: string }[] }

/** A case study card (App\Content\PageData::cases). `illustrative` cases always show a label. */
export type CaseItem = {
  slug: string; sector: string; origin: string; region: string; media: string; services: string[]
  illustrative: boolean; title: string; summary: string; client: string
}

export type CaseStory = CaseItem & {
  entity: string | null; challenge: string; outcome: string
  steps: { title: string; text: string }[]; delivered: string[]
}

export type StatItem = { key: string; value: string; label: string; icon: string | null; group?: 'primary' | 'secondary' }
export type CountryItem = { code: string; name: string }
export type FaqItem = { id: number; question: string; answer: string }
export type TestimonialItem = { id: number; quote: string; context: string | null; author: string | null; verified: boolean }

export type ReviewStatus = 'missing' | 'machine' | 'review' | 'approved'

export type PageProps = {
  pageKey: string
  title: string
  seo: { title: string; description: string }
  reviewStatus: ReviewStatus
  sections: Section[]
  services: ServiceItem[]
  regions: RegionItem[]
  /** Present on pages with newsroom sections. */
  articles?: NewsItem[]
  /** Present on pages with a case grid. */
  cases?: CaseItem[]
  /** Present on a business solution's page. */
  related?: ServiceItem[]
  serviceSlug?: string
  /** Present on a region's page. */
  region?: { slug: string; name: string; industries: string[] }
  otherRegions?: RegionItem[]
  breadcrumbs?: { label: string; href?: string }[]
  /** Present on the FAQ page: every published question with its category. */
  faqLibrary?: { categories: { key: string; label: string }[]; items: (FaqItem & { category: string })[] }
  industries: Record<string, string>
  serviceGroups: Record<ServiceGroup, string>
  journey: JourneyStep[]
  stats: StatItem[]
  countries: CountryItem[]
  faqs: FaqItem[]
  testimonials: TestimonialItem[]
  media: Record<string, MediaItem>
}

/** Props of a business solution's detail page (App\Content\PageData::forService). */
export type ServicePageProps = {
  pageKey: 'service'
  title: string
  seo: { title: string; description: string }
  reviewStatus: ReviewStatus
  service: {
    slug: string; icon: string; media: string; group: ServiceGroup
    title: string; tagline: string; overview: string | null; scope: string[]; note: string | null; cta: string
  }
  related: ServiceItem[]
  serviceFaqs: FaqItem[]
  journey: JourneyStep[]
  serviceGroups: Record<ServiceGroup, string>
  stats: StatItem[]
  media: Record<string, MediaItem>
}

/** Props of a region's detail page (App\Content\PageData::forRegion). */
export type RegionPageProps = {
  pageKey: 'region'
  title: string
  regionsTitle: string
  seo: { title: string; description: string }
  reviewStatus: ReviewStatus
  region: RegionItem
  industries: Record<string, string>
  related: ServiceItem[]
  otherRegions: RegionItem[]
  media: Record<string, MediaItem>
}

/** Props of a news article page (App\Content\PageData::forArticle). */
export type NewsArticlePageProps = {
  pageKey: 'article'
  title: string
  newsTitle: string
  seo: { title: string; description: string }
  reviewStatus: ReviewStatus
  article: NewsArticle
  related: NewsItem[]
  latest: { slug: string; title: string }[]
  media: Record<string, MediaItem>
}

/** Props of a case study page (App\Content\PageData::forCaseStudy). */
export type CaseStudyPageProps = {
  pageKey: 'case'
  title: string
  casesTitle: string
  seo: { title: string; description: string }
  reviewStatus: ReviewStatus
  caseStudy: CaseStory
  region: RegionItem | null
  regions: RegionItem[]
  industries: Record<string, string>
  countries: CountryItem[]
  related: ServiceItem[]
  otherCases: CaseItem[]
  media: Record<string, MediaItem>
}
