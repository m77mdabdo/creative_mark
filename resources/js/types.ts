/* Props contract with app/Content/HomePageData.php and HandleInertiaRequests.php */
export type Button = { label: string; href: string; style: 'gold' | 'outline' | 'link' }
export type Card = { id: string; title: string | null; text: string | null; icon?: string; media?: string }
export type MotionPreset = 'none' | 'fade-up' | 'reveal-mask' | 'zoom-in' | 'parallax-slow' | 'stagger-cards' | 'counter' | 'line-draw'
export type Section = {
  id: string
  type: 'hero' | 'whyInvest' | 'journey' | 'countries' | 'sectors' | 'trackRecord' | 'services' | 'map' | 'faq' | 'cta' | 'testimonials'
  eyebrow?: string | null; title?: string | null; subtitle?: string | null; body?: string | null
  media?: string; media2?: string; limit?: number
  buttons: Button[]; cards: Card[]
  style: { theme: 'dark' | 'charcoal' | 'ivory' | 'white'; overlay: number; padding: 'sm' | 'md' | 'lg'; align: 'start' | 'center'; bgMedia?: string }
  motion: { preset: MotionPreset; intensity: number; delay: number }
}
export type MediaItem = { src: string; srcset: string; alt: string }
export type ServiceItem = { slug: string; icon: string; media: string; order: number; title: string; tagline: string; summary: string; cta: string }
export type RegionItem = { slug: string; media: string; name: string; capital: string; bestFor: string; description: string; opportunities: string[]; industries: string[]; services: string[] }
export type HomeProps = {
  seo: { title: string; description: string }
  reviewStatus: 'missing' | 'machine' | 'review' | 'approved'
  sections: Section[]
  services: ServiceItem[]
  regions: RegionItem[]
  industries: Record<string, string>
  stats: { key: string; value: string; label: string }[]
  countries: { code: string; name: string }[]
  faqs: { id: number; question: string; answer: string }[]
  testimonials: { id: number; quote: string; context: string | null; author: string | null; verified: boolean }[]
  media: Record<string, MediaItem>
}
export type LocaleInfo = { code: string; tag: string; native: string; dir: 'ltr' | 'rtl' }
export type SharedProps = {
  locale: LocaleInfo & { available: LocaleInfo[] }
  site: {
    nav: { label: string; href: string; mega?: 'services' }[]
    cta: { label: string; href: string } | null
    megaServices: { slug: string; icon: string; title: string; tagline: string }[]
    offices: { key: string; country: string; address: string }[]
    email: string | null; phone: string | null; whatsapp: string | null
    social: Partial<Record<'instagram' | 'facebook' | 'linkedin', string>>
  }
}
