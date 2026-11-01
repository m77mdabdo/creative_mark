import type { HomeProps, Section } from '@/types'

/** Every section renderer receives its own block and the page data it may need (services, regions, media…). */
export type SectionProps = { section: Section; page: HomeProps }

export const isLightTheme = (section: Section) => section.style.theme === 'ivory' || section.style.theme === 'white'
