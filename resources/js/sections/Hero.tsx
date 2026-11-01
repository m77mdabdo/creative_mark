import { useSite } from '@/hooks/useSite'
import ButtonGroup from '@/components/ButtonGroup'
import Media from '@/components/Media'
import { HighlightedTitle } from './shared/SectionHeading'
import type { SectionProps } from './shared/types'

/** Full-bleed cinematic opening (FR-010). The scrim keeps text above 4.5:1 whatever the photo. */
export default function Hero({ section, page }: SectionProps) {
  const { t } = useSite()
  const { overlay, align } = section.style
  const centered = align === 'center'
  const rise = (delay: number) => ({ animation: `cm-rise 1.1s var(--ease-out) ${delay}s both` })

  return <section id={section.id} data-type="hero" aria-labelledby={`${section.id}-title`}
    className="th-dark relative flex min-h-[88svh] items-end overflow-hidden lg:min-h-[100svh]">
    <div className="absolute inset-0 overflow-hidden" aria-hidden>
      <Media media={section.media ? page.media[section.media] : undefined} eager decorative className="h-full w-full"
        {...(section.motion.preset === 'none' ? {} : { 'data-motion': 'zoom' })} />
    </div>
    <div className="absolute inset-0" aria-hidden style={{
      background: `linear-gradient(180deg, rgba(8,8,9,${Math.min(0.9, overlay / 140)}) 0%, rgba(8,8,9,${overlay / 100}) 45%, rgba(8,8,9,.95) 100%)`,
    }} />

    <div className={`wrap relative pb-14 pt-32 md:pb-24 lg:pt-44 ${centered ? 'text-center' : ''}`}>
      <div className={`max-w-5xl ${centered ? 'mx-auto' : ''}`}>
        {section.eyebrow && <p className={`eyebrow mb-6 flex items-center gap-3 ${centered ? 'justify-center' : ''}`} style={rise(0.1)}>
          <span className="h-px w-10 bg-gold" aria-hidden />{section.eyebrow}{centered && <span className="h-px w-10 bg-gold" aria-hidden />}
        </p>}
        <h1 id={`${section.id}-title`} className="font-display t-display-xl text-white" style={rise(0.2)}>
          <HighlightedTitle title={section.title ?? ''} highlight={section.highlight} />
        </h1>
        {section.subtitle && <p className={`t-body-l mt-6 max-w-2xl text-white/80 md:mt-8 ${centered ? 'mx-auto' : ''}`} style={rise(0.35)}>{section.subtitle}</p>}
        <div style={rise(0.5)}><ButtonGroup buttons={section.buttons} className={`mt-8 text-white md:mt-10 ${centered ? 'justify-center' : ''}`} /></div>
      </div>
    </div>

    <div className="absolute bottom-6 end-6 hidden items-center gap-3 text-[10px] uppercase tracking-[.3em] text-white/50 md:flex" aria-hidden>
      <span className="h-10 w-px bg-gradient-to-b from-transparent to-gold" />{t('scroll')}
    </div>
  </section>
}
