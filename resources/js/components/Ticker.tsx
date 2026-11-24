import { useEffect, useRef, useState } from 'react'
import SmartLink from '@/components/SmartLink'

export type TickerItem = { key: string; text: string; href?: string | null }

type Props = {
  items: TickerItem[]
  /** Gold badge at the start; hidden when empty. */
  label?: string | null
  /** `sm` is the slim bar above the menu, `md` the newsroom strip. */
  size?: 'sm' | 'md'
  /** Scroll speed. Without it the strip takes the stylesheet's default 60 s per loop. */
  pxPerSecond?: number
  className?: string
}

/**
 * Continuous strip of links or short messages. The row is rendered twice and slides on a loop (paused on hover or
 * focus); short rows repeat so the strip is always full. With reduced motion it becomes a static, horizontally
 * scrollable row. The copies are hidden from assistive tech and from the tab order.
 */
export default function Ticker({ items, label, size = 'md', pxPerSecond, className = '' }: Props) {
  const viewport = useRef<HTMLDivElement>(null)
  const firstRow = useRef<HTMLUListElement>(null)
  const [repeat, setRepeat] = useState(1)
  const [duration, setDuration] = useState<number>()

  useEffect(() => {
    const box = viewport.current
    const row = firstRow.current
    if (!box || !row) return
    const measure = () => {
      const setWidth = row.offsetWidth / repeat
      if (setWidth === 0) return
      const needed = Math.max(1, Math.ceil(box.offsetWidth / setWidth))
      if (needed !== repeat) setRepeat(needed)
      else if (pxPerSecond) setDuration(Math.max(10, row.offsetWidth / pxPerSecond))
    }
    measure()
    const observer = new ResizeObserver(measure)
    observer.observe(box)
    return () => observer.disconnect()
  }, [items, repeat, pxPerSecond])

  if (items.length === 0) return null
  const small = size === 'sm'
  const linkClass = `whitespace-nowrap transition hover:text-gold ${small ? 'px-5 text-[13px] text-white/85' : 'px-6 text-sm text-white/80'}`
  const row = (copy: number) => <ul ref={copy === 0 ? firstRow : undefined} className="flex shrink-0 items-center" aria-hidden={copy > 0 || undefined}>
    {Array.from({ length: repeat }, (_, r) => items.map(item => {
      const visible = copy === 0 && r === 0
      return <li key={`${r}-${item.key}`} className="flex items-center" aria-hidden={!visible && copy === 0 ? true : undefined}>
        {item.href
          ? <SmartLink href={item.href} className={linkClass} {...(visible ? {} : { tabIndex: -1 })}>{item.text}</SmartLink>
          : <span className={linkClass}>{item.text}</span>}
        <span className="h-1 w-1 shrink-0 rounded-full bg-gold/70" aria-hidden />
      </li>
    }))}
  </ul>

  return <div className={`flex items-stretch overflow-hidden ${className}`}>
    {label && <p className={`relative z-10 flex shrink-0 items-center gap-2 bg-gold font-bold uppercase text-ink ${small ? 'px-3 text-[11px] tracking-[.12em] md:px-4' : 'px-4 text-xs tracking-[.14em] md:px-6'}`}>
      <span className="h-2 w-2 animate-pulse rounded-full bg-ink motion-reduce:animate-none" aria-hidden />{label}
    </p>}
    <div ref={viewport} className={`news-ticker flex min-w-0 flex-1 items-center overflow-hidden motion-reduce:overflow-x-auto ${small ? '' : 'py-3'}`}>
      <div className="news-ticker-track flex w-max" style={duration ? { animationDuration: `${duration}s` } : undefined}>{row(0)}{row(1)}</div>
    </div>
  </div>
}
