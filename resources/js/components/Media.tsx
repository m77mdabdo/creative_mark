import type { CSSProperties } from 'react'
import type { MediaItem } from '@/types'

/** data-* attributes are passed through so motion presets (data-parallax, data-motion) can target the image. */
type DataAttributes = { [key: `data-${string}`]: string | number | undefined }

type Props = DataAttributes & {
  media?: MediaItem
  className?: string
  /** `sizes` hint for the responsive srcset. */
  sizes?: string
  /** Above-the-fold images load eagerly with high priority (LCP). */
  eager?: boolean
  /** Decorative images get an empty alt so screen readers skip them. */
  decorative?: boolean
  style?: CSSProperties
}

/** Responsive image from the page's media map. The parent sets the box size, so there is no layout shift. */
export default function Media({ media, className = '', sizes = '100vw', eager, decorative, style, ...data }: Props) {
  if (!media) return <div className={`bg-ink-2 ${className}`} style={style} aria-hidden />

  return <img
    src={media.src}
    srcSet={media.srcset}
    sizes={sizes}
    alt={decorative ? '' : media.alt}
    loading={eager ? 'eager' : 'lazy'}
    fetchPriority={eager ? 'high' : undefined}
    decoding="async"
    className={`object-cover ${className}`}
    style={style}
    {...data}
  />
}
