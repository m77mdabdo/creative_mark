import { ChevronRight } from 'lucide-react'
import SmartLink from './SmartLink'
import { useSite } from '@/hooks/useSite'

/** Home › current page, under the page hero (Phase C/D §3). */
export default function Breadcrumbs({ current }: { current: string }) {
  const { t } = useSite()
  return <nav aria-label={t('breadcrumb')} className="border-b border-white/[.06] bg-ink">
    <ol className="wrap flex min-h-12 items-center gap-2 text-xs text-white/60">
      <li><SmartLink href="/" className="hover:text-gold">{t('homeLabel')}</SmartLink></li>
      <li aria-hidden><ChevronRight size={12} className="flip-rtl" /></li>
      <li aria-current="page" className="text-white/90">{current}</li>
    </ol>
  </nav>
}
