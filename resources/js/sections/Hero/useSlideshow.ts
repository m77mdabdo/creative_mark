import { useCallback, useEffect, useRef, useState, type RefObject } from 'react'
import { gsap, prefersReducedMotion } from '@/motion/gsap'

/** Seconds each slide stays on screen: the photo travels from close-up to wide during this time. */
export const SLIDE_SECONDS = 7

const REVEAL_SECONDS = 1.5
const START_SCALE = 1.28 // close
const END_SCALE = 1.02 // wide

type Refs = {
  layers: RefObject<(HTMLDivElement | null)[]>
  photos: RefObject<(HTMLDivElement | null)[]>
  progress: RefObject<(HTMLSpanElement | null)[]>
}

/**
 * Drives the hero slideshow with GSAP:
 *  1. the incoming slide is revealed by a wipe from the inline end while the outgoing one turns away (scale + slight rotation),
 *  2. the photo then pulls back from close-up to wide for SLIDE_SECONDS,
 *  3. the progress bar fills in sync and advances to the next slide.
 * Pausing freezes all three. With reduced motion there is no autoplay and slides change with a plain cross-fade.
 */
export function useSlideshow(count: number, { layers, photos, progress }: Refs) {
  const [active, setActive] = useState(0)
  const [playing, setPlaying] = useState(true)
  const [reduced, setReduced] = useState(false)
  const previous = useRef<number | null>(null)
  const timeline = useRef<gsap.core.Timeline | null>(null)

  useEffect(() => {
    const isReduced = prefersReducedMotion()
    setReduced(isReduced)
    if (isReduced) setPlaying(false)
  }, [])

  const goTo = useCallback((index: number) => {
    setActive(current => {
      if (index === current) return current
      previous.current = current
      return (index + count) % count
    })
  }, [count])

  // Build the timeline for the active slide.
  useEffect(() => {
    if (count < 1) return
    const layer = layers.current?.[active]
    const photo = photos.current?.[active]
    const bar = progress.current?.[active]
    const outgoing = previous.current !== null ? layers.current?.[previous.current] : null
    if (!layer || !photo) return

    const rtl = document.documentElement.dir === 'rtl'
    const hiddenEdge = rtl ? 'inset(0 100% 0 0)' : 'inset(0 0 0 100%)'

    layers.current?.forEach((el, i) => {
      if (!el) return
      el.style.zIndex = i === active ? '2' : el === outgoing ? '1' : '0'
      if (i !== active && el !== outgoing) gsap.set(el, { autoAlpha: 0 })
    })
    progress.current?.forEach((el, i) => el && gsap.set(el, { scaleX: i < active ? 1 : 0 }))

    const tl = gsap.timeline({ paused: !playing })

    if (reduced) {
      tl.fromTo(layer, { autoAlpha: 0 }, { autoAlpha: 1, duration: outgoing ? 0.4 : 0 })
      gsap.set(photo, { scale: 1 })
      if (bar) gsap.set(bar, { scaleX: 1 })
      timeline.current = tl
      return () => { tl.kill() }
    }

    if (outgoing) {
      tl.fromTo(layer, { autoAlpha: 1, clipPath: hiddenEdge }, { clipPath: 'inset(0 0% 0 0%)', duration: REVEAL_SECONDS, ease: 'expo.inOut' }, 0)
      tl.to(outgoing, { scale: 0.92, rotate: rtl ? 2 : -2, autoAlpha: 0, duration: REVEAL_SECONDS, ease: 'power3.inOut' }, 0)
      tl.set(outgoing, { scale: 1, rotate: 0 })
    } else {
      tl.set(layer, { autoAlpha: 1, clipPath: 'inset(0 0% 0 0%)' }, 0)
    }
    tl.fromTo(photo, { scale: START_SCALE }, { scale: END_SCALE, duration: SLIDE_SECONDS + REVEAL_SECONDS, ease: 'power2.out' }, 0)
    if (bar) tl.fromTo(bar, { scaleX: 0 }, { scaleX: 1, duration: SLIDE_SECONDS, ease: 'none' }, REVEAL_SECONDS * 0.5)
    // The next slide starts once the bar is full, while this photo is still drifting.
    tl.addLabel('end', SLIDE_SECONDS + REVEAL_SECONDS * 0.5)
    tl.call(() => goTo(active + 1), [], 'end')

    timeline.current = tl
    return () => { tl.kill() }
  }, [active, reduced]) // eslint-disable-line react-hooks/exhaustive-deps

  // Play / pause without rebuilding the timeline.
  useEffect(() => {
    const tl = timeline.current
    if (!tl) return
    if (playing && !reduced) tl.play()
    else tl.pause()
  }, [playing, reduced, active])

  // Pause while the tab is hidden so slides do not race ahead.
  useEffect(() => {
    const onVisibility = () => {
      if (document.hidden) timeline.current?.pause()
      else if (playing && !reduced) timeline.current?.play()
    }
    document.addEventListener('visibilitychange', onVisibility)
    return () => document.removeEventListener('visibilitychange', onVisibility)
  }, [playing, reduced])

  return { active, playing, reduced, goTo, togglePlaying: () => setPlaying(p => !p) }
}
