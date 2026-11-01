/** Props shared with every page by App\Http\Middleware\HandleInertiaRequests. */

export type LocaleInfo = { code: string; tag: string; native: string; dir: 'ltr' | 'rtl' }

export type NavItem = { label: string; href: string; mega?: 'services' }

import type { UiStrings } from '@/i18n/uiStrings'

export type SharedProps = {
  ui: UiStrings
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
    translationNotice: boolean
  }
}
