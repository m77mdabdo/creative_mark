import { useEffect, useRef, useState } from 'react'
import { ArrowUpRight, Pause, Play } from 'lucide-react'
import Media from '@/components/Media'
import SmartLink from '@/components/SmartLink'
import { useSite } from '@/hooks/useSite'
import { useSlideshow } from '../Hero/useSlideshow'
import { HighlightedTitle } from '../shared/SectionHeading'
import type { SectionProps } from '../shared/types'
import { categoryKey, formatNewsDate } from './format'
import NewsTicker from './NewsTicker'

const STORIES = 5

/** The lead story: the newest featured article, otherwise the newest one. */
export const leadStory = (articles: SectionProps['page']['articles'] = []) => articles.find(a => a.featured) ?? articles[0]

/** Live Riyadh clock for the broadcast bar (rendered after mount, so server and client markup match). */
function RiyadhClock({ localeTag }: { localeTag: string }) {
  const [now, setNow] = useState<Date | null>(null)
  useEffect(() => {
    setNow(new Date())
    const id = window.setInterval(() => setNow(new Date()), 30_000)
    return () => window.clearInterval(id)
  }, [])
  if (!now) return null
  return <time dateTime={now.toISOString()} className="tabular-nums">
    {new Intl.DateTimeFormat(localeTag, { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Riyadh', numberingSystem: 'latn' }).format(now)}
  </time>
}

/**
 * Broadcast-style opening: the top stories fill the screen one after another (photo wipe + slow pull-back,
 * shared with the home hero), with a newsroom bar, a "top stories" playlist and the latest-headlines ticker.
 * Pausable; with reduced motion nothing moves and stories change only when chosen.
 */
export default function NewsLead({ section, page }: SectionProps) {
  const { t, locale } = useSite()
  const articles = page.articles ?? []
  const lead = leadStory(articles)
  const stories = lead ? [lead, ...articles.filter(a => a !== lead)].slice(0, STORIES) : []
  const layers = useRef<(HTMLDivElement | null)[]>([])
  const photos = useRef<(HTMLDivElement | null)[]>([])
  const progress = useRef<(HTMLSpanElement | null)[]>([])
  const show = useSlideshow(stories.length, { layers, photos, progress })
  const story = stories[show.active]
  const today = new Date().toISOString().slice(0, 10)

  return <section id={section.id} aria-labelledby={`${section.id}-title`} className="th-dark relative isolate flex min-h-[100svh] flex-col overflow-hidden bg-black">
    {/* Photos */}
    <div className="absolute inset-0 -z-10" aria-hidden>
      {stories.map((s, i) => <div key={s.slug} ref={el => { layers.current[i] = el }} className="absolute inset-0 overflow-hidden" style={{ opacity: i === 0 ? 1 : 0 }}>
        <div ref={el => { photos.current[i] = el }} className="h-full w-full will-change-transform">
          <Media media={page.media[s.media]} decorative eager={i === 0} sizes="100vw" className="h-full w-full" />
        </div>
      </div>)}
      <div className="absolute inset-0 z-[3] bg-gradient-to-t from-black via-black/55 to-black/30" />
      <div className="absolute inset-0 z-[3] bg-gradient-to-r from-black/70 via-transparent to-transparent rtl:bg-gradient-to-l" />
      <div className="hero-vignette absolute inset-0 z-[3]" />
      <div className="hero-grain absolute z-[3] motion-reduce:hidden" />
    </div>

    {/* Newsroom bar */}
    <div className="wrap pt-28 md:pt-32">
      <div className="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-white/15 pb-4 text-xs text-white/75">
        <span className="flex items-center gap-2 rounded-full bg-gold px-3 py-1 font-bold text-ink">
          <span className="relative flex h-2 w-2" aria-hidden><span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-ink/60 motion-reduce:animate-none" /><span className="relative inline-flex h-2 w-2 rounded-full bg-ink" /></span>
          {t('liveNewsroom')}
        </span>
        <span>{t('riyadhTime')} <RiyadhClock localeTag={locale.tag} /></span>
        <span className="hidden sm:inline"><time dateTime={today}>{formatNewsDate(today, locale.tag)}</time></span>
        <span className="ms-auto">{articles.length} {t('stories')}</span>
      </div>
      <h1 id={`${section.id}-title`} className="font-display mt-6 max-w-2xl text-3xl leading-tight text-white md:text-5xl">
        <HighlightedTitle title={section.title ?? ''} highlight={section.highlight} />
      </h1>
    </div>

    {/* Active story + playlist */}
    {story && <div className="wrap mt-auto grid gap-10 pb-10 pt-16 lg:grid-cols-[1fr_360px] lg:items-end lg:gap-16 lg:pb-14">
      <div key={story.slug} className="news-story-in" aria-live="polite">
        <p className="mb-5 flex flex-wrap items-center gap-3 text-xs">
          <span className="rounded-sm bg-white px-2.5 py-1 font-bold uppercase tracking-wider text-ink">{t(categoryKey(story.category))}</span>
          <span className="text-white/75"><time dateTime={story.date}>{formatNewsDate(story.date, locale.tag)}</time> · {story.source.name}</span>
        </p>
        <h2 className="font-display max-w-4xl text-[2.1rem] leading-[1.08] text-white sm:text-5xl lg:text-7xl">
          <SmartLink href={`/news/${story.slug}`} className="transition hover:text-gold">{story.title}</SmartLink>
        </h2>
        <p className="mt-5 max-w-2xl text-white/80 md:text-xl">{story.excerpt}</p>
        <div className="mt-8 flex flex-wrap items-center gap-3">
          <SmartLink href={`/news/${story.slug}`} className="btn btn-gold">{t('readStory')}<ArrowUpRight size={16} className="flip-rtl" aria-hidden /></SmartLink>
          {!show.reduced && <button type="button" onClick={show.togglePlaying} className="grid h-12 w-12 place-items-center rounded-full border border-white/30 text-white transition hover:border-gold hover:text-gold"
            aria-label={show.playing ? t('pause') : t('play')}>{show.playing ? <Pause size={16} /> : <Play size={16} />}</button>}
        </div>
      </div>

      <ol aria-label={t('topStories')} className="grid grid-cols-5 gap-2 lg:block lg:space-y-1 lg:rounded-[var(--cm-radius)] lg:border lg:border-white/10 lg:bg-black/45 lg:p-2 lg:backdrop-blur-md">
        {stories.map((s, i) => <li key={s.slug}>
          <button type="button" onClick={() => show.goTo(i)} aria-current={i === show.active ? 'true' : undefined} aria-label={`${t('showStory')}: ${s.title}`}
            className={`group relative block w-full text-start transition lg:flex lg:items-center lg:gap-3 lg:rounded-[calc(var(--cm-radius)-1px)] lg:p-2 ${i === show.active ? 'lg:bg-white/10' : 'lg:hover:bg-white/5'}`}>
            <span className="relative hidden h-14 w-20 shrink-0 overflow-hidden rounded-sm lg:block">
              <Media media={page.media[s.media]} decorative sizes="80px" className="h-full w-full" />
            </span>
            <span className="hidden min-w-0 lg:block">
              <span className="block text-[11px] text-gold">{String(i + 1).padStart(2, '0')} · {t(categoryKey(s.category))}</span>
              <span className={`line-clamp-2 text-sm leading-snug ${i === show.active ? 'text-white' : 'text-white/65'}`}>{s.title}</span>
            </span>
            {/* Progress: a segment under each story (mobile) or a line under the active row (desktop) */}
            <span className="relative block h-1 overflow-hidden rounded-full bg-white/20 lg:absolute lg:inset-x-2 lg:bottom-0 lg:h-0.5 lg:bg-transparent" aria-hidden>
              <span ref={el => { progress.current[i] = el }} className="absolute inset-0 origin-left scale-x-0 bg-gold rtl:origin-right" />
            </span>
          </button>
        </li>)}
      </ol>
    </div>}

    <NewsTicker items={articles.slice(0, 8)} />
  </section>
}
