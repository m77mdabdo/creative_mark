import type { NewsCategory } from '@/types'
import type { UiKey } from '@/i18n/uiStrings'

/** Gregorian dates with Latin digits in every language, matching the rest of the site. */
export const formatNewsDate = (date: string, localeTag: string, month: 'long' | 'short' = 'long') =>
  new Intl.DateTimeFormat(localeTag, { day: 'numeric', month, year: 'numeric', calendar: 'gregory', numberingSystem: 'latn', timeZone: 'UTC' })
    .format(new Date(`${date}T00:00:00Z`))

export const categoryKey = (category: NewsCategory) => `newsCat_${category}` as UiKey

/** Rough reading time at 200 words a minute (Chinese counted by characters). */
export const readingMinutes = (text: string) => {
  const words = text.trim().split(/\s+/).length
  const cjk = (text.match(/[一-鿿]/g) ?? []).length
  return Math.max(1, Math.round((words + cjk / 2) / 200))
}
