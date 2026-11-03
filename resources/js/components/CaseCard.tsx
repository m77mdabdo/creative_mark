import { ArrowUpRight, MapPin } from 'lucide-react'
import { useSite } from '@/hooks/useSite'
import type { CaseItem, CountryItem, MediaItem, RegionItem } from '@/types'
import Flag from './Flag'
import Media from './Media'
import SmartLink from './SmartLink'

type Props = {
  item: CaseItem; media?: MediaItem; industries: Record<string, string>; countries: CountryItem[]; regions: RegionItem[]
  reverse?: boolean; compact?: boolean
}

/** Editorial case row: photo on one side, client, sector, region and the question the case answers on the other. */
export default function CaseCard({ item, media, industries, countries, regions, reverse = false, compact = false }: Props) {
  const { t } = useSite()
  const country = countries.find(c => c.code === item.origin)
  const region = regions.find(r => r.slug === item.region)

  return <SmartLink href={`/case-studies/${item.slug}`} className={`group grid overflow-hidden rounded-[var(--cm-radius)] border border-white/10 bg-white/[.02] transition hover:border-gold/50 ${compact ? '' : 'md:grid-cols-[1.1fr_1fr]'}`}>
    <div className={`relative overflow-hidden ${compact ? 'aspect-[16/10]' : 'aspect-[16/10] md:aspect-auto md:min-h-[380px]'} ${reverse && !compact ? 'md:order-last' : ''}`}>
      <Media media={media} sizes={compact ? '(min-width: 1024px) 50vw, 100vw' : '(min-width: 768px) 55vw, 100vw'} className="absolute inset-0 h-full w-full transition duration-[1.4s] ease-[var(--ease-out)] group-hover:scale-[1.05]" />
      <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" aria-hidden />
      {item.illustrative && <span className="absolute start-4 top-4 rounded-full border border-white/30 bg-black/60 px-3 py-1 text-[11px] text-white/85 backdrop-blur-sm">{t('illustrative')}</span>}
    </div>
    <div className="flex flex-col p-6 md:p-10">
      <p className="mb-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-white/60">
        {country && <span className="flex items-center gap-2"><Flag code={country.code} size={20} />{country.name}</span>}
        <span className="rounded-full border border-gold/40 px-2.5 py-0.5 text-gold">{industries[item.sector] ?? item.sector}</span>
        {region && <span className="flex items-center gap-1"><MapPin size={12} aria-hidden />{region.name}</span>}
      </p>
      <p className="mb-2 text-sm text-white/55">{item.client}</p>
      <h3 className={`font-display leading-tight transition group-hover:text-gold ${compact ? 'text-2xl' : 'text-3xl md:text-4xl'}`}>{item.title}</h3>
      <p className="mt-4 text-white/70 measure">{item.summary}</p>
      <span className="mt-auto flex items-center gap-2 pt-8 text-sm font-semibold text-gold">{t('readCase')}<ArrowUpRight size={16} className="flip-rtl transition group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5" aria-hidden /></span>
    </div>
  </SmartLink>
}
