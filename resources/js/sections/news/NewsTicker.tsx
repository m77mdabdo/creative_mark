import Ticker from '@/components/Ticker'
import { useSite } from '@/hooks/useSite'
import type { NewsItem } from '@/types'

/** "Latest" strip of headlines on the newsroom. */
export default function NewsTicker({ items }: { items: NewsItem[] }) {
  const { t } = useSite()

  return <Ticker label={t('latest')} items={items.map(item => ({ key: item.slug, text: item.title, href: `/news/${item.slug}` }))}
    className="border-y border-white/10 bg-black/60" />
}
