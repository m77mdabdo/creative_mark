import { useLayoutEffect, useEffect, type RefObject } from 'react'
import { router } from '@inertiajs/react'
import gsap from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import Lenis from 'lenis'

/*
 * Motion presets live in code (ADR-0007); the CMS only chooses a preset, intensity and delay per section.
 * Markup is fully visible without JS. When motion is allowed, `motion-ready` on <html> sets the start states
 * and GSAP plays each preset once at 85% of the viewport. Everything is reverted on unmount / Inertia navigation.
 *
 *   data-motion="fade-up | reveal-mask | line | zoom | stagger"   data-parallax="8"   data-counter="1000"
 *   data-delay="120" (ms)   data-intensity="0-100"
 */
gsap.registerPlugin(ScrollTrigger)

const useIsoLayoutEffect = typeof window === 'undefined' ? useEffect : useLayoutEffect

export const prefersReducedMotion = () =>
  typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches

const num = (el: Element, attr: string, fallback: number) => {
  const v = Number((el as HTMLElement).dataset[attr])
  return Number.isFinite(v) ? v : fallback
}

export function useMotion(scope: RefObject<HTMLElement | null>, key: string) {
  useIsoLayoutEffect(() => {
    const root = scope.current
    if (!root) return
    const html = document.documentElement
    if (prefersReducedMotion()) {
      html.classList.remove('motion-ready')
      return
    }
    html.classList.add('motion-ready')
    const rtl = html.dir === 'rtl'
    const start = 'top 85%'
    const ease = 'expo.out'

    const ctx = gsap.context(() => {
      gsap.utils.toArray<HTMLElement>('[data-motion="fade-up"]').forEach(el => {
        const k = num(el, 'intensity', 100) / 100
        gsap.fromTo(el, { opacity: 0, y: 24 * k }, { opacity: 1, y: 0, duration: 0.9, delay: num(el, 'delay', 0) / 1000, ease, scrollTrigger: { trigger: el, start, once: true } })
      })
      gsap.utils.toArray<HTMLElement>('[data-motion="reveal-mask"]').forEach(el => {
        gsap.fromTo(el, { clipPath: 'inset(0 0 100% 0)' }, { clipPath: 'inset(0 0 0% 0)', duration: 1.1, delay: num(el, 'delay', 0) / 1000, ease: 'expo.inOut', scrollTrigger: { trigger: el, start, once: true } })
      })
      gsap.utils.toArray<HTMLElement>('[data-motion="stagger"]').forEach(group => {
        const items = Array.from(group.children) as HTMLElement[]
        // 80 ms apart, at most 6 in sequence; the rest arrive with the sixth
        gsap.fromTo(items, { opacity: 0, y: 28 }, { opacity: 1, y: 0, duration: 0.9, ease, stagger: (i: number) => Math.min(i, 5) * 0.08, scrollTrigger: { trigger: group, start, once: true } })
      })
      gsap.utils.toArray<HTMLElement>('[data-motion="line"]').forEach(el => {
        gsap.fromTo(el, { scaleX: 0 }, { scaleX: 1, transformOrigin: rtl ? 'right center' : 'left center', duration: 0.7, ease: 'power3.out', scrollTrigger: { trigger: el, start, once: true } })
      })
      gsap.utils.toArray<HTMLElement>('[data-motion="zoom"]').forEach(el => {
        gsap.fromTo(el, { scale: 1.12 }, { scale: 1, duration: 2.4, ease: 'power2.out' })
      })
      gsap.utils.toArray<HTMLElement>('[data-parallax]').forEach(el => {
        const travel = num(el, 'parallax', 8) * (num(el, 'intensity', 100) / 100)
        gsap.fromTo(el, { yPercent: -travel }, { yPercent: travel, ease: 'none', scrollTrigger: { trigger: el.parentElement ?? el, start: 'top bottom', end: 'bottom top', scrub: true } })
      })
      gsap.utils.toArray<HTMLElement>('[data-counter]').forEach(el => {
        const target = num(el, 'counter', 0)
        const suffix = el.dataset.suffix ?? ''
        const o = { v: 0 }
        gsap.to(o, {
          v: target, duration: 1.6, ease: 'power2.out',
          scrollTrigger: { trigger: el, start: 'top 75%', once: true },
          onStart: () => { el.textContent = `0${suffix}` },
          onUpdate: () => { el.textContent = `${Math.round(o.v).toLocaleString('en')}${suffix}` },
        })
      })
    }, root)

    ScrollTrigger.refresh()
    return () => ctx.revert()
  }, [key])
}

/** Lenis smooth scroll on desktop pointer devices only, never with reduced motion or touch (UX-05). */
export function useSmoothScroll() {
  useEffect(() => {
    if (prefersReducedMotion() || !window.matchMedia('(pointer: fine) and (min-width: 1024px)').matches) return
    const lenis = new Lenis({ duration: 1.1, anchors: true })
    lenis.on('scroll', ScrollTrigger.update)
    const tick = (t: number) => lenis.raf(t * 1000)
    gsap.ticker.add(tick)
    gsap.ticker.lagSmoothing(0)
    const off = router.on('navigate', () => lenis.scrollTo(0, { immediate: true }))
    return () => { off(); gsap.ticker.remove(tick); lenis.destroy() }
  }, [])
}
