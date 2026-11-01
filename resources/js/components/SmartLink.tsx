import type { ReactNode } from 'react'
import { Link } from '@inertiajs/react'
import { useSite } from '@/hooks/useSite'

type Props = { href: string; className?: string; children: ReactNode; 'aria-label'?: string }

/**
 * One link component for CMS-provided hrefs (already validated by SectionSchema::safeHref):
 * internal paths become locale-prefixed Inertia links, external ones open in a new tab.
 */
export default function SmartLink({ href, className, children, ...rest }: Props) {
  const site = useSite()

  if (href.startsWith('/')) {
    return <Link href={site.href(href)} className={className} {...rest}>{children}</Link>
  }

  const external = href.startsWith('http')
  return <a href={href} className={className} {...rest} {...(external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}>{children}</a>
}
