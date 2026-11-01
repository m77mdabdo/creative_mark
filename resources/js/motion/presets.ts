import { gsap } from './gsap'

/*
 * Motion presets (Phase C/D §12). Each one animates elements marked in the markup:
 *
 *   data-motion="fade-up | reveal-mask | stagger | line | zoom"
 *   data-parallax="8"        travel in % (scaled by data-intensity)
 *   data-counter="1000"      animated number, data-suffix="+"
 *   data-delay="120"         ms, data-intensity="0-100"
 *
 * Only transform, opacity and clip-path are animated. Scroll-triggered presets play once at 85% of the viewport.
 */

const START = 'top 85%'
const EASE_OUT = 'expo.out'

const read = (el: HTMLElement, key: string, fallback: number) => {
  const value = Number(el.dataset[key])
  return Number.isFinite(value) ? value : fallback
}
const delayOf = (el: HTMLElement) => read(el, 'delay', 0) / 1000
const intensityOf = (el: HTMLElement) => read(el, 'intensity', 100) / 100
const all = (selector: string) => gsap.utils.toArray<HTMLElement>(selector)

export type PresetContext = { rtl: boolean }

export const PRESETS: Record<string, (ctx: PresetContext) => void> = {
  fadeUp() {
    all('[data-motion="fade-up"]').forEach(el => gsap.fromTo(el,
      { opacity: 0, y: 24 * intensityOf(el) },
      { opacity: 1, y: 0, duration: 0.9, delay: delayOf(el), ease: EASE_OUT, scrollTrigger: { trigger: el, start: START, once: true } }))
  },

  revealMask() {
    all('[data-motion="reveal-mask"]').forEach(el => gsap.fromTo(el,
      { clipPath: 'inset(0 0 100% 0)' },
      { clipPath: 'inset(0 0 0% 0)', duration: 1.1, delay: delayOf(el), ease: 'expo.inOut', scrollTrigger: { trigger: el, start: START, once: true } }))
  },

  /** Children arrive 80 ms apart; after the sixth the rest arrive together. */
  stagger() {
    all('[data-motion="stagger"]').forEach(group => gsap.fromTo(Array.from(group.children),
      { opacity: 0, y: 28 },
      { opacity: 1, y: 0, duration: 0.9, ease: EASE_OUT, stagger: (i: number) => Math.min(i, 5) * 0.08, scrollTrigger: { trigger: group, start: START, once: true } }))
  },

  line({ rtl }) {
    all('[data-motion="line"]').forEach(el => gsap.fromTo(el,
      { scaleX: 0 },
      { scaleX: 1, transformOrigin: rtl ? 'right center' : 'left center', duration: 0.7, ease: 'power3.out', scrollTrigger: { trigger: el, start: START, once: true } }))
  },

  /** Hero media settles from 1.12 to 1 on load. */
  zoom() {
    all('[data-motion="zoom"]').forEach(el => gsap.fromTo(el, { scale: 1.12 }, { scale: 1, duration: 2.4, ease: 'power2.out' }))
  },

  parallax() {
    all('[data-parallax]').forEach(el => {
      const travel = read(el, 'parallax', 8) * intensityOf(el)
      gsap.fromTo(el, { yPercent: -travel }, { yPercent: travel, ease: 'none', scrollTrigger: { trigger: el.parentElement ?? el, start: 'top bottom', end: 'bottom top', scrub: true } })
    })
  },

  /** The final value is already in the HTML; the tween only runs when motion is allowed. */
  counter() {
    all('[data-counter]').forEach(el => {
      const target = read(el, 'counter', 0)
      const suffix = el.dataset.suffix ?? ''
      const state = { value: 0 }
      gsap.to(state, {
        value: target, duration: 1.6, ease: 'power2.out',
        scrollTrigger: { trigger: el, start: 'top 75%', once: true },
        onStart: () => { el.textContent = `0${suffix}` },
        onUpdate: () => { el.textContent = `${Math.round(state.value).toLocaleString('en')}${suffix}` },
      })
    })
  },
}
