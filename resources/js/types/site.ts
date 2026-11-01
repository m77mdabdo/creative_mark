/** Props shared with every page by App\Http\Middleware\HandleInertiaRequests. */

export type LocaleInfo = { code: string; tag: string; native: string; dir: 'ltr' | 'rtl' }

export type NavItem = { label: string; href: string; mega?: 'services' }

export type SharedProps = {
  locale: LocaleInfo & { available: LocaleInfo[] }
  site: {
    nav: NavItem[]
    cta: { label: string; href: string } | null
    megaServices: { slug: string; icon: string; title: string; tagline: string }[]
    offices: { key: string; country: string; address: string }[]
    email: string | null
    phone: string | null
    whatsapp: string | null
    social: Partial<Record<'instagram' | 'facebook' | 'linkedin', string>>
  }
}
