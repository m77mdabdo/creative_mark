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
  | 'statement' | 'imageText' | 'cards'

/** One frame of the hero slideshow (copy already resolved to the visitor's locale). */
export type Slide = {
  id: string; media: string; eyebrow?: string | null; title?: string | null; highlight?: string | null; subtitle?: string | null
  /** Region slug whose investment opportunities are listed under the copy. */
  region?: string
}

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
  style: { theme: SectionTheme; overlay: number; padding: 'sm' | 'md' | 'lg'; align: 'start' | 'center'; bgMedia?: string }
  motion: { preset: MotionPreset; intensity: number; delay: number }
}

export type MediaItem = { src: string; srcset: string; alt: string }

export type ServiceItem = { slug: string; icon: string; media: string; order: number; title: string; tagline: string; summary: string; cta: string }

export type RegionItem = {
  slug: string; media: string; name: string; capital: string; bestFor: string
  description: string; opportunities: string[]; industries: string[]; services: string[]
}

export type StatItem = { key: string; value: string; label: string; icon: string | null }
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
  industries: Record<string, string>
  stats: StatItem[]
  countries: CountryItem[]
  faqs: FaqItem[]
  testimonials: TestimonialItem[]
  media: Record<string, MediaItem>
}
