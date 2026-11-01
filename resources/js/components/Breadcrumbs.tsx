import { ChevronRight } from 'lucide-react'
import SmartLink from './SmartLink'
import { useSite } from '@/hooks/useSite'

type Crumb = { label: string; href?: string }

/** Home › … › current page, under the page hero (Phase C/D §3). */
export default function Breadcrumbs({ trail }: { trail: Crumb[] }) {
  const { t } = useSite()
  const items: Crumb[] = [{ label: t('homeLabel'), href: '/' }, ...trail]

  return <nav aria-label={t('breadcrumb')} className="border-b border-white/[.06] bg-ink">
    <ol className="wrap flex min-h-12 flex-wrap items-center gap-x-2 gap-y-1 py-2 text-xs text-white/60">
      {items.map((item, i) => <li key={i} className="flex items-center gap-2">
        {i > 0 && <ChevronRight size={12} className="flip-rtl" aria-hidden />}
        {item.href && i < items.length - 1
          ? <SmartLink href={item.href} className="inline-flex min-h-6 items-center hover:text-gold">{item.label}</SmartLink>
          : <span aria-current="page" className="text-white/90">{item.label}</span>}
      </li>)}
    </ol>
  </nav>
}
