import { useEffect, useRef, useState } from 'react'
import Media from '@/components/Media'
import SmartLink from '@/components/SmartLink'
import { prefersReducedMotion } from '@/motion/gsap'
import type { Card, MediaItem } from '@/types'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/** Seconds a tile takes to cross its own width; the loop's length follows the number of tiles. */
const SECONDS_PER_TILE = 4.5

/**
 * "Clients we founded companies for": an endless strip of client images, logos or silent videos. It moves right to
 * left in left-to-right languages and left to right in Arabic and Urdu (the shared ticker keyframes), pauses when
 * the pointer or keyboard focus is on it, and the tile under the pointer grows. Each tile may link elsewhere.
 * The row is rendered twice; copies are hidden from assistive tech and the tab order. With reduced motion it is a
 * still row that scrolls sideways and videos show their poster image.
 */
export default function ClientMarquee(props: SectionProps) {
  const { section, page } = props
  const viewport = useRef<HTMLDivElement>(null)
  const firstRow = useRef<HTMLUListElement>(null)
  const [repeat, setRepeat] = useState(1)
  const [still, setStill] = useState(false)
  const cards = section.cards

  useEffect(() => setStill(prefersReducedMotion()), [])
  useEffect(() => {
    const box = viewport.current
    const row = firstRow.current
    if (!box || !row) return
    const measure = () => {
      const setWidth = row.offsetWidth / repeat
      if (setWidth > 0) setRepeat(r => Math.max(r, Math.ceil(box.offsetWidth / setWidth)))
    }
    measure()
    const observer = new ResizeObserver(measure)
    observer.observe(box)
    return () => observer.disconnect()
  }, [cards, repeat])

  if (cards.length === 0) return null
  const duration = Math.max(24, cards.length * repeat * SECONDS_PER_TILE)

  const row = (copy: number) => <ul ref={copy === 0 ? firstRow : undefined} className="flex shrink-0 items-center" aria-hidden={copy > 0 || undefined}>
    {Array.from({ length: repeat }, (_, r) => cards.map(card => {
      const visible = copy === 0 && r === 0
      return <li key={`${r}-${card.id}`} className="shrink-0 px-2.5 md:px-3.5" aria-hidden={!visible && copy === 0 ? true : undefined}>
        <Tile card={card} media={card.media ? page.media[card.media] : undefined} focusable={visible} still={still} />
      </li>
    }))}
  </ul>

  return <SectionShell {...props}>
    <div className="wrap"><div className="mb-12 md:mb-16"><SectionHeading section={section} /></div></div>
    <div ref={viewport} role="region" aria-label={section.title ?? undefined}
      className="client-marquee relative overflow-hidden py-8 motion-reduce:overflow-x-auto [mask-image:linear-gradient(90deg,transparent,#000_8%,#000_92%,transparent)]">
      <div className="client-marquee-track flex w-max" style={{ animationDuration: `${duration}s` }}>{row(0)}{row(1)}</div>
    </div>
  </SectionShell>
}

function Tile({ card, media, focusable, still }: { card: Card; media?: MediaItem; focusable: boolean; still: boolean }) {
  const body = <figure className="group/tile relative h-[180px] w-[150px] overflow-hidden rounded-[14px] border border-white/10 bg-ink-2 shadow-[0_24px_50px_-28px_rgba(0,0,0,.9)] transition duration-500 ease-[var(--ease-out)] hover:z-10 hover:scale-[1.12] hover:border-gold/70 hover:shadow-[0_30px_70px_-20px_rgba(235,189,108,.35)] focus-within:scale-[1.12] focus-within:border-gold/70 md:h-[260px] md:w-[220px]">
    {card.video
      ? <video className="absolute inset-0 h-full w-full object-cover" src={card.video} poster={media?.src} muted loop playsInline autoPlay={!still}
          preload={still ? 'none' : 'metadata'} aria-hidden />
      : <Media media={media} sizes="220px" className="absolute inset-0 h-full w-full transition duration-700 group-hover/tile:scale-105" decorative={!!card.title} />}
    <div className="absolute inset-0 bg-gradient-to-t from-black/85 via-black/10 to-transparent" aria-hidden />
    <span className="absolute inset-x-5 top-0 h-px bg-gradient-to-r from-transparent via-gold/80 to-transparent opacity-0 transition duration-500 group-hover/tile:opacity-100" aria-hidden />
    {(card.title || card.text) && <figcaption className="absolute inset-x-0 bottom-0 p-4 text-white md:p-5">
      {card.title && <span className="block text-sm font-semibold leading-snug md:text-base">{card.title}</span>}
      {card.text && <span className="mt-1 block text-xs text-white/70">{card.text}</span>}
    </figcaption>}
  </figure>

  if (!card.href) return body
  return <SmartLink href={card.href} className="block rounded-[14px] focus-visible:outline-offset-4" {...(focusable ? {} : { tabIndex: -1 })}
    aria-label={card.title ?? undefined}>{body}</SmartLink>
}
