import type { PageProps, Section } from '@/types'

/** Every section renderer receives its own block and the page data it may need (services, regions, media…). */
export type SectionProps = { section: Section; page: PageProps }

export const isLightTheme = (section: Section) => section.style.theme === 'ivory' || section.style.theme === 'white'
