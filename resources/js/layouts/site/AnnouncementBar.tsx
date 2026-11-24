import Ticker from '@/components/Ticker'
import { useSite } from '@/hooks/useSite'
import type { AnnouncementSpeed } from '@/types'

const PX_PER_SECOND: Record<AnnouncementSpeed, number> = { slow: 35, normal: 55, fast: 85 }

/**
 * Slim scrolling bar above the menu on every page (Settings › Announcement bar). Its height is the stylesheet's
 * --cm-announce, which also pushes the page content down while the bar is shown.
 */
export default function AnnouncementBar() {
  const { site } = useSite()
  const bar = site.announcement
  if (!bar) return null

  return <Ticker size="sm" label={bar.label} pxPerSecond={PX_PER_SECOND[bar.speed]}
    items={bar.items.map((item, i) => ({ key: String(i), text: item.text, href: item.href }))}
    className="announce-bar h-[var(--cm-announce-h)] border-b border-gold/25 bg-[#050505]" />
}
