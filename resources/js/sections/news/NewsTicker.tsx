import SmartLink from '@/components/SmartLink'
import { useSite } from '@/hooks/useSite'
import type { NewsItem } from '@/types'

/**
 * "Latest" strip of headlines. The list is rendered twice and slides continuously (pauses on hover/focus);
 * with reduced motion it becomes a static, horizontally scrollable row. The duplicate is hidden from assistive tech.
 */
export default function NewsTicker({ items }: { items: NewsItem[] }) {
  const { t } = useSite()
  if (items.length === 0) return null
  const row = (hidden: boolean) => <ul className="flex shrink-0 items-center" aria-hidden={hidden || undefined}>
    {items.map(item => <li key={item.slug} className="flex items-center">
      <SmartLink href={`/news/${item.slug}`} className="whitespace-nowrap px-6 text-sm text-white/80 transition hover:text-gold" {...(hidden ? { tabIndex: -1 } : {})}>{item.title}</SmartLink>
      <span className="h-1 w-1 rounded-full bg-gold/70" aria-hidden />
    </li>)}
  </ul>

  return <div className="flex items-stretch overflow-hidden border-y border-white/10 bg-black/60">
    <p className="relative z-10 flex shrink-0 items-center gap-2 bg-gold px-4 text-xs font-bold uppercase tracking-[.14em] text-ink md:px-6">
      <span className="h-2 w-2 animate-pulse rounded-full bg-ink motion-reduce:animate-none" aria-hidden />{t('latest')}
    </p>
    <div className="news-ticker min-w-0 flex-1 overflow-hidden py-3 motion-reduce:overflow-x-auto">
      <div className="news-ticker-track flex w-max">{row(false)}{row(true)}</div>
    </div>
  </div>
}
