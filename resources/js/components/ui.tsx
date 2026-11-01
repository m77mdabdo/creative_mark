import type { CSSProperties, ReactNode } from 'react'
import { Link } from '@inertiajs/react'
import { ArrowRight, Award, BadgeCheck, Briefcase, Building2, Calculator, Compass, FileCheck2, Globe2, Landmark, LineChart, Map, MapPinned, Plane, Shapes, Sparkles, Target, TrendingUp, Users, Workflow, type LucideIcon } from 'lucide-react'
import type { Button, MediaItem } from '@/types'
import { useSite } from '@/lib/useSite'

/* Curated icon set the CMS may reference by name; unknown names fall back to Sparkles. */
const ICONS: Record<string, LucideIcon> = { Award, BadgeCheck, Briefcase, Building2, Calculator, Compass, FileCheck2, Globe2, Landmark, LineChart, Map, MapPinned, Plane, Shapes, Target, TrendingUp, Users, Workflow }

export function Icon({ name, size = 20, stroke = 1.5, className = '' }: { name?: string; size?: number; stroke?: number; className?: string }) {
  const C = (name && ICONS[name]) || Sparkles
  return <C size={size} strokeWidth={stroke} className={className} aria-hidden />
}

/** Responsive placeholder image; fixed box from the parent avoids layout shift. Decorative when alt is "". */
export function Img({ media, className = '', sizes = '100vw', eager, decorative, style }: { media?: MediaItem; className?: string; sizes?: string; eager?: boolean; decorative?: boolean; style?: CSSProperties }) {
  if (!media) return <div className={`bg-ink-2 ${className}`} style={style} aria-hidden />
  return <img src={media.src} srcSet={media.srcset} sizes={sizes} alt={decorative ? '' : media.alt}
    loading={eager ? 'eager' : 'lazy'} fetchPriority={eager ? 'high' : undefined} decoding="async"
    className={`object-cover ${className}`} style={style} />
}

export const Logo = ({ className = '' }: { className?: string }) =>
  <img src="/brand/wordmark-gold.png" alt="Creative Mark" width={805} height={368} className={`w-auto ${className}`} />

/* CMS links are validated on the server (SectionSchema::safeHref); internal ones get the locale prefix. */
export function SmartLink({ href, className, children, ...rest }: { href: string; className?: string; children: ReactNode; 'aria-label'?: string }) {
  const { href: h } = useSite()
  if (href.startsWith('/')) return <Link href={h(href)} className={className} {...rest}>{children}</Link>
  const external = href.startsWith('http')
  return <a href={href} className={className} {...rest} {...(external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}>{children}</a>
}

export function Buttons({ buttons, className = '' }: { buttons: Button[]; className?: string }) {
  if (!buttons.length) return null
  return <div className={`flex flex-wrap items-center gap-3 ${className}`}>
    {buttons.map((b, i) => <SmartLink key={i} href={b.href} className={`btn btn-${b.style}`}>{b.label}<ArrowRight size={15} className="btn-arrow flip-rtl" aria-hidden /></SmartLink>)}
  </div>
}

/** CMS text is always rendered as text: paragraphs split on blank lines, never HTML. */
export const Paras = ({ text, className = '' }: { text?: string | null; className?: string }) =>
  text ? <>{text.split(/\n{2,}/).map((p, i) => <p key={i} className={className}>{p}</p>)}</> : null

const brand = { width: 16, height: 16, viewBox: '0 0 24 24', fill: 'currentColor', 'aria-hidden': true } as const
export const SocialIcon = ({ name }: { name: string }) => {
  if (name === 'instagram') return <svg {...brand}><path d="M12 7.3a4.7 4.7 0 1 0 0 9.4 4.7 4.7 0 0 0 0-9.4Zm0 7.7a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm6-7.9a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0ZM21.9 8.1c-.1-1.6-.4-3-1.6-4.2-1.2-1.2-2.6-1.5-4.2-1.6C14.5 2.2 9.5 2.2 7.9 2.3 6.3 2.4 4.9 2.7 3.7 3.9 2.5 5.1 2.2 6.5 2.1 8.1 2 9.7 2 14.3 2.1 15.9c.1 1.6.4 3 1.6 4.2 1.2 1.2 2.6 1.5 4.2 1.6 1.6.1 6.6.1 8.2 0 1.6-.1 3-.4 4.2-1.6 1.2-1.2 1.5-2.6 1.6-4.2.1-1.6.1-6.2 0-7.8Zm-2.1 9.7a3.2 3.2 0 0 1-1.8 1.8c-1.3.5-4.3.4-5.6.4s-4.4.1-5.6-.4a3.2 3.2 0 0 1-1.8-1.8c-.5-1.3-.4-4.3-.4-5.8s-.1-4.4.4-5.8a3.2 3.2 0 0 1 1.8-1.8c1.3-.5 4.3-.4 5.6-.4s4.4-.1 5.6.4a3.2 3.2 0 0 1 1.8 1.8c.5 1.3.4 4.3.4 5.8s.1 4.4-.4 5.8Z" /></svg>
  if (name === 'facebook') return <svg {...brand}><path d="M13.5 21.9v-7.4H16l.4-3h-2.9V9.6c0-.8.3-1.5 1.5-1.5h1.5V5.5c-.3 0-1.2-.1-2.2-.1-2.2 0-3.7 1.3-3.7 3.8v2.3H8.1v3h2.5v7.4h2.9Z" /></svg>
  return <svg {...brand}><path d="M6.9 21H3.3V9.2h3.6V21ZM5.1 7.6a2.1 2.1 0 1 1 0-4.2 2.1 2.1 0 0 1 0 4.2ZM21 21h-3.6v-5.7c0-1.4 0-3.1-1.9-3.1s-2.2 1.5-2.2 3V21H9.7V9.2h3.4v1.6h.1c.5-.9 1.6-1.9 3.4-1.9 3.6 0 4.3 2.4 4.3 5.5V21Z" /></svg>
}
