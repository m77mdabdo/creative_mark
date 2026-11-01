import type { ReactNode } from 'react'
import Media from '@/components/Media'
import { parallaxMotion } from './motionAttributes'
import { isLightTheme, type SectionProps } from './types'

type Props = SectionProps & { children: ReactNode; className?: string }

/** Section wrapper: theme colours, vertical rhythm, optional photographic background with a scrim. */
export default function SectionShell({ section, page, children, className = '' }: Props) {
  const background = section.style.bgMedia ? page.media[section.style.bgMedia] : undefined
  const scrim = isLightTheme(section) ? '247,245,239' : '8,8,9'

  return <section id={section.id} data-type={section.type} aria-labelledby={section.title ? `${section.id}-title` : undefined}
    className={`th-${section.style.theme} relative overflow-hidden pad-${section.style.padding} ${className}`}>
    {background && <div className="absolute inset-0 overflow-hidden" aria-hidden>
      <Media media={background} decorative className="absolute inset-x-0 -inset-y-[10%] h-[120%] w-full" {...parallaxMotion(section, 6)} />
      <div className="absolute inset-0" style={{ background: `rgba(${scrim},${section.style.overlay / 100})` }} />
    </div>}
    <div className="relative">{children}</div>
  </section>
}
