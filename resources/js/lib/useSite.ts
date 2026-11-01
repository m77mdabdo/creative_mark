import { usePage } from '@inertiajs/react'
import type { SharedProps } from '@/types'
import { ui, type UiKey } from './ui'

export function useSite() {
  const { props } = usePage<SharedProps>()
  const code = props.locale.code
  return {
    ...props,
    L: (k: UiKey) => ui(k, code),
    /** Prefix an internal CMS path with the current locale; external and anchor links pass through. */
    href: (h: string) => (h.startsWith('/') && !h.startsWith('//') ? `/${code}${h === '/' ? '' : h}` : h),
  }
}
