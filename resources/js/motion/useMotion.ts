import { useEffect, useLayoutEffect, type RefObject } from 'react'
import { gsap, prefersReducedMotion, ScrollTrigger } from './gsap'
import { PRESETS } from './presets'

const useIsomorphicLayoutEffect = typeof window === 'undefined' ? useEffect : useLayoutEffect

/**
 * Runs every motion preset inside `scope` (ADR-0007). Markup is fully visible without JS; `motion-ready`
 * on <html> applies the start states only once motion is allowed. Animations and ScrollTriggers are reverted
 * when the page unmounts or `key` changes (Inertia navigation, locale switch).
 */
export function useMotion(scope: RefObject<HTMLElement | null>, key: string) {
  useIsomorphicLayoutEffect(() => {
    const root = scope.current
    const html = document.documentElement
    if (!root || prefersReducedMotion()) {
      html.classList.remove('motion-ready')
      return
    }

    html.classList.add('motion-ready')
    const ctx = gsap.context(() => {
      const presetContext = { rtl: html.dir === 'rtl' }
      Object.values(PRESETS).forEach(run => run(presetContext))
    }, root)
    ScrollTrigger.refresh()

    return () => ctx.revert()
  }, [key])
}
