import { ArrowUpRight } from 'lucide-react'
import { useSite } from '@/hooks/useSite'
import { categoryKey, formatNewsDate } from '@/sections/news/format'
import type { MediaItem, NewsItem } from '@/types'
import Media from './Media'
import SmartLink from './SmartLink'

/** Story card: photo, topic, date, headline, summary and the publication it comes from. */
export default function NewsCard({ item, media, large = false }: { item: NewsItem; media?: MediaItem; large?: boolean }) {
  const { t, locale } = useSite()

  return <SmartLink href={`/news/${item.slug}`} className="group flex h-full flex-col overflow-hidden rounded-[var(--cm-radius)] border border-white/10 bg-white/[.02] transition hover:border-gold/40">
    <div className={`relative overflow-hidden ${large ? 'aspect-[16/9]' : 'aspect-[3/2]'}`}>
      <Media media={media} sizes={large ? '(min-width: 1024px) 66vw, 100vw' : '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw'}
        className="absolute inset-0 h-full w-full transition duration-[1.2s] ease-[var(--ease-out)] group-hover:scale-[1.05]" />
      <span className="absolute start-4 top-4 rounded-full bg-black/70 px-3 py-1 text-[11px] font-semibold text-gold backdrop-blur-sm">{t(categoryKey(item.category))}</span>
    </div>
    <div className="flex flex-1 flex-col p-5 md:p-6">
      <p className="mb-3 text-xs text-white/50"><time dateTime={item.date}>{formatNewsDate(item.date, locale.tag)}</time> · {item.source.name}</p>
      <h3 className={`font-display leading-snug transition group-hover:text-gold ${large ? 'text-2xl md:text-4xl' : 'text-xl md:text-2xl'}`}>{item.title}</h3>
      <p className="mt-3 line-clamp-3 text-sm text-white/65">{item.excerpt}</p>
      <span className="mt-auto flex items-center gap-1.5 pt-5 text-sm text-gold">{t('readStory')}<ArrowUpRight size={15} className="flip-rtl transition group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5" aria-hidden /></span>
    </div>
  </SmartLink>
}
