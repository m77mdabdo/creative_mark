import { usePage } from '@inertiajs/react'
import type { SharedProps } from '@/types'
import type { UiKey } from '@/i18n/uiStrings'

/** Shared site props plus two helpers: `t` for interface strings and `href` for locale-prefixed links. */
export function useSite() {
  const { props } = usePage<SharedProps>()
  const code = props.locale.code

  return {
    ...props,
    t: (key: UiKey): string => props.ui[key] ?? key,
    /** Prefixes internal CMS paths with the current locale; external, mailto, tel and anchors pass through. */
    href: (path: string) => (path.startsWith('/') && !path.startsWith('//') ? `/${code}${path === '/' ? '' : path}` : path),
  }
}
