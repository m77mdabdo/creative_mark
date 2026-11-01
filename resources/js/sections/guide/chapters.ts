import type { PageProps, Section } from '@/types'

/** Chapters are the page's guideChapter sections, numbered in page order. */
export const guideChapters = (page: PageProps): Section[] => page.sections.filter(s => s.type === 'guideChapter')

export const chapterNumber = (n: number) => String(n).padStart(2, '0')

/** "2026-07" → "Jul 2026" in the visitor's language; plain years pass through. */
export function formatSourceDate(date: string | undefined, localeTag: string): string | null {
  if (!date) return null
  const [y, m, d] = date.split('-').map(Number)
  if (!m) return String(y)
  return new Intl.DateTimeFormat(localeTag, { year: 'numeric', month: 'short', ...(d ? { day: 'numeric' } : {}) }).format(new Date(Date.UTC(y, m - 1, d || 1)))
}
