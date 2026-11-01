import { useRef } from 'react'
import { Pause, Play } from 'lucide-react'
import ButtonGroup from '@/components/ButtonGroup'
import Media from '@/components/Media'
import { useSite } from '@/hooks/useSite'
import type { Section, Slide } from '@/types'
import { HighlightedTitle } from '../shared/SectionHeading'
import type { SectionProps } from '../shared/types'
import { useSlideshow } from './useSlideshow'

/** Without slides the hero shows its own image and copy as a single frame. */
const slidesOf = (section: Section): Slide[] => section.slides.length
  ? section.slides
  : [{ id: 'main', media: section.media ?? '', eyebrow: section.eyebrow, title: section.title, highlight: section.highlight, subtitle: section.subtitle }]

/**
 * Cinematic opening (FR-010): a slideshow of Saudi landmarks. Each photo starts close and pulls back,
 * then the next one wipes in while the copy changes with it. A pause control and per-slide buttons satisfy
 * WCAG 2.2.2; with reduced motion nothing moves until the visitor picks a slide.
 */
export default function Hero({ section, page }: SectionProps) {
  const { t } = useSite()
  const slides = slidesOf(section)
  const layers = useRef<(HTMLDivElement | null)[]>([])
  const photos = useRef<(HTMLDivElement | null)[]>([])
  const progress = useRef<(HTMLSpanElement | null)[]>([])
  const show = useSlideshow(slides.length, { layers, photos, progress })

  const { overlay, align } = section.style
  const centered = align === 'center'
  const current = slides[show.active]
  const opportunities = page.regions.find(r => r.slug === current.region)?.opportunities ?? []
  const scrim = `linear-gradient(180deg, rgba(8,8,9,${Math.min(0.9, overlay / 140)}) 0%, rgba(8,8,9,${overlay / 100}) 45%, rgba(8,8,9,.95) 100%)`

  return <section id={section.id} data-type="hero" aria-labelledby={`${section.id}-title`} aria-roledescription="carousel"
    className="th-dark relative flex min-h-[88svh] items-end overflow-hidden lg:min-h-[100svh]">
    {/* The page's single h1 is the brand line; slide headlines are visual and announced by the live region. */}
    <h1 id={`${section.id}-title`} className="sr-only">{slides[0].title}</h1>

    <div className="absolute inset-0 overflow-hidden [perspective:1600px]" aria-hidden>
      {slides.map((slide, i) => <div key={slide.id} ref={el => { layers.current[i] = el }} className="absolute inset-0 overflow-hidden"
        style={{ opacity: i === 0 ? 1 : 0, zIndex: i === 0 ? 2 : 0 }}>
        <div ref={el => { photos.current[i] = el }} className="h-full w-full will-change-transform">
          <Media media={page.media[slide.media]} eager={i === 0} decorative sizes="100vw" className="h-full w-full" />
        </div>
      </div>)}
      <div className="hero-light absolute inset-0 z-[3]" />
      <div className="absolute inset-0 z-[3]" style={{ background: scrim }} />
      <div className="hero-vignette absolute inset-0 z-[3]" />
      <div className="hero-grain pointer-events-none absolute z-[3]" />
    </div>

    <div className={`wrap relative z-10 pb-36 pt-32 md:pb-40 lg:pt-44 ${centered ? 'text-center' : ''}`}>
      <div key={current.id} className={`max-w-5xl ${centered ? 'mx-auto' : ''}`} aria-hidden>
        {current.eyebrow && <p className={`eyebrow mb-6 flex items-center gap-3 ${centered ? 'justify-center' : ''}`} style={rise(0.15)}>
          <span className="h-px w-10 bg-gold" />{current.eyebrow}{centered && <span className="h-px w-10 bg-gold" />}
        </p>}
        <p className="font-display t-display-xl text-white" style={rise(0.3)}>
          <HighlightedTitle title={current.title ?? ''} highlight={current.highlight} />
        </p>
        {current.subtitle && <p className={`t-body-l mt-6 max-w-2xl text-white/85 md:mt-8 ${centered ? 'mx-auto' : ''}`} style={rise(0.45)}>{current.subtitle}</p>}
        <Opportunities items={opportunities} centered={centered} label={t('investmentOpportunities')} />
      </div>
      <p className="sr-only" aria-live={show.playing ? 'off' : 'polite'}>
        {t('slide')} {show.active + 1} {t('of')} {slides.length}: {current.title}. {current.subtitle} {opportunities.join('، ')}
      </p>
      <ButtonGroup buttons={section.buttons} className={`mt-8 text-white md:mt-10 ${centered ? 'justify-center' : ''}`} />
    </div>

    {slides.length > 1 && <div className="absolute inset-x-0 bottom-0 z-10">
      <div className="wrap flex items-center gap-4 pb-6 md:gap-6 md:pb-8">
        <button type="button" onClick={show.togglePlaying} aria-label={show.playing ? t('pause') : t('play')}
          className="grid h-11 w-11 shrink-0 place-items-center rounded-full border border-white/25 text-white transition hover:border-gold hover:text-gold">
          {show.playing ? <Pause size={15} aria-hidden /> : <Play size={15} aria-hidden />}
        </button>
        <span className="hidden shrink-0 text-xs tabular-nums tracking-[.2em] text-white/70 sm:block" dir="ltr" aria-hidden>
          {String(show.active + 1).padStart(2, '0')} / {String(slides.length).padStart(2, '0')}
        </span>
        <ol className="flex flex-1 items-center gap-2">
          {slides.map((slide, i) => <li key={slide.id} className="flex-1">
            <button type="button" onClick={() => show.goTo(i)} aria-label={`${t('goToSlide')} ${i + 1}: ${slide.title ?? ''}`} aria-current={i === show.active ? 'true' : undefined}
              className="group flex h-11 w-full items-center">
              <span className="relative block h-px w-full overflow-hidden bg-white/25 transition-[height] group-hover:h-[3px]">
                <span ref={el => { progress.current[i] = el }} className="absolute inset-0 origin-left scale-x-0 bg-gold rtl:origin-right" />
              </span>
            </button>
          </li>)}
        </ol>
      </div>
    </div>}
  </section>
}

/** The region's opportunities from the catalogue, as gold-edged chips (kept in sync with the Regions module). */
function Opportunities({ items, centered, label }: { items: string[]; centered: boolean; label: string }) {
  if (!items.length) return null
  return <div className={`mt-6 flex flex-wrap items-center gap-2 ${centered ? 'justify-center' : ''}`} style={rise(0.6)}>
    <span className="me-1 text-[11px] font-semibold uppercase tracking-[.18em] text-gold">{label}</span>
    {items.map(item => <span key={item} className="rounded-full border border-gold/45 bg-black/35 px-3 py-1 text-xs text-white/90 backdrop-blur-sm">{item}</span>)}
  </div>
}

const rise = (delay: number) => ({ animation: `cm-rise 1.1s var(--ease-out) ${delay}s both` })
