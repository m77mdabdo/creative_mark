import Icon from '@/components/Icon'
import type { Section } from '@/types'
import { groupMotion } from './motionAttributes'

const columns = (count: number) => (count === 4 ? 'lg:grid-cols-4' : count === 2 ? 'lg:grid-cols-2' : 'lg:grid-cols-3')

/** Numbered icon cards joined by hairlines; a swipe row with a visible next card on phones. */
export default function FeatureCardGrid({ section }: { section: Section }) {
  return <div {...groupMotion(section)}
    className={`swipe hair border bg-[color-mix(in_srgb,currentColor_10%,transparent)] md:grid md:grid-flow-row md:grid-cols-2 md:gap-px md:overflow-visible ${columns(section.cards.length)}`}>
    {section.cards.map((card, i) => <article key={card.id}
      className={`th-${section.style.theme} group relative p-8 transition-colors duration-500 hover:bg-[color-mix(in_srgb,var(--cm-gold)_6%,transparent)] md:p-9`}>
      <div className="mb-10 flex items-start justify-between">
        {card.icon ? <Icon name={card.icon} className="accent" size={30} stroke={1.2} /> : <span />}
        <span className="font-display accent text-2xl opacity-60" aria-hidden>{String(i + 1).padStart(2, '0')}</span>
      </div>
      <h3 className="mb-3 text-lg font-semibold leading-snug">{card.title}</h3>
      <p className="muted text-sm leading-relaxed">{card.text}</p>
      <span className="absolute inset-x-8 bottom-0 h-px origin-left scale-x-0 bg-gold transition-transform duration-700 group-hover:scale-x-100 rtl:origin-right" aria-hidden />
    </article>)}
  </div>
}
