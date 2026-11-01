import type { Section } from '@/types'

/*
 * Translate the CMS motion setting of a section into data-attributes read by motion/presets.ts.
 * "none" returns no attributes, so the element simply renders.
 */

/** Heading and text blocks: reveal-mask when chosen, otherwise fade-up. */
export const headingMotion = ({ motion }: Section) =>
  motion.preset === 'none' ? {} : {
    'data-motion': motion.preset === 'reveal-mask' ? 'reveal-mask' : 'fade-up',
    'data-delay': motion.delay,
    'data-intensity': motion.intensity,
  }

/** Lists and grids: children arrive one after another. */
export const groupMotion = ({ motion }: Section) => (motion.preset === 'none' ? {} : { 'data-motion': 'stagger' })

/** Background or inline images drift with scroll. */
export const parallaxMotion = ({ motion }: Section, travel = 8) =>
  motion.preset === 'none' ? {} : { 'data-parallax': travel, 'data-intensity': motion.intensity }
