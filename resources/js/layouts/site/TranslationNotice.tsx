import { Languages } from 'lucide-react'
import { useSite } from '@/hooks/useSite'

/** RULE-06: a locale whose text is not yet reviewed says so, under the header. */
export default function TranslationNotice() {
  const { t, locale } = useSite()
  return <div role="status" data-testid="translation-notice"
    className="fixed inset-x-0 top-16 z-40 flex items-center justify-center gap-2 bg-gold px-4 py-1.5 text-center text-xs font-medium text-ink xl:top-[88px]">
    <Languages size={13} aria-hidden />{locale.code === 'ar' ? t('pendingAr') : t('pending')}
  </div>
}
