import { useEffect } from 'react'
import { router } from '@inertiajs/react'
import Lenis from 'lenis'
import { gsap, prefersReducedMotion, ScrollTrigger } from './gsap'

/** Lenis smooth scroll on desktop pointer devices only; never on touch or with reduced motion (UX-05). */
export function useSmoothScroll() {
  useEffect(() => {
    const desktopPointer = window.matchMedia('(pointer: fine) and (min-width: 1024px)').matches
    if (prefersReducedMotion() || !desktopPointer) return

    const lenis = new Lenis({ duration: 1.1, anchors: true })
    lenis.on('scroll', ScrollTrigger.update)
    const tick = (time: number) => lenis.raf(time * 1000)
    gsap.ticker.add(tick)
    gsap.ticker.lagSmoothing(0)
    const stopListening = router.on('navigate', () => lenis.scrollTo(0, { immediate: true }))

    return () => {
      stopListening()
      gsap.ticker.remove(tick)
      lenis.destroy()
    }
  }, [])
}
