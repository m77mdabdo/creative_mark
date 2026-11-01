import type { ReactNode } from 'react'
import type { HomeProps, Section } from '@/types'
import { Img } from '@/components/ui'

export type SectionProps = { s: Section; data: HomeProps }

/** Maps the CMS preset to the motion attribute used on the section's heading block. */
export const headMotion = (s: Section) =>
  s.motion.preset === 'none' ? {} : { 'data-motion': s.motion.preset === 'reveal-mask' ? 'reveal-mask' : 'fade-up', 'data-delay': s.motion.delay, 'data-intensity': s.motion.intensity }

export const groupMotion = (s: Section) => (s.motion.preset === 'none' ? {} : { 'data-motion': 'stagger' })

const light = (s: Section) => s.style.theme === 'ivory' || s.style.theme === 'white'

/** Section shell: theme, padding and an optional background image with a token-based overlay. */
export function Shell({ s, data, children, className = '' }: SectionProps & { children: ReactNode; className?: string }) {
  const bg = s.style.bgMedia ? data.media[s.style.bgMedia] : undefined
  return <section id={s.id} data-type={s.type} aria-labelledby={s.title ? `${s.id}-title` : undefined}
    className={`th-${s.style.theme} relative overflow-hidden pad-${s.style.padding} ${className}`}>
    {bg && <div className="absolute inset-0 overflow-hidden" aria-hidden>
      <Img media={bg} decorative className="absolute -inset-y-[10%] inset-x-0 h-[120%] w-full" {...(s.motion.preset !== 'none' ? { 'data-parallax': 6 } as object : {})} />
      <div className="absolute inset-0" style={{ background: light(s) ? `rgba(247,245,239,${s.style.overlay / 100})` : `rgba(8,8,9,${s.style.overlay / 100})` }} />
    </div>}
    <div className="relative">{children}</div>
  </section>
}

export function Head({ s, center, className = '' }: { s: Section; center?: boolean; className?: string }) {
  const c = center ?? s.style.align === 'center'
  return <div className={`flex max-w-3xl flex-col ${c ? 'mx-auto items-center text-center' : ''} ${className}`} {...headMotion(s)}>
    {s.eyebrow && <p className="eyebrow mb-5 flex items-center gap-3">{!c && <span className="h-px w-8 bg-current" aria-hidden />}{s.eyebrow}</p>}
    {s.title && <h2 id={`${s.id}-title`} className="font-display t-display-l">{s.title}</h2>}
    {s.subtitle && <p className="mt-5 t-body-l muted measure">{s.subtitle}</p>}
  </div>
}
