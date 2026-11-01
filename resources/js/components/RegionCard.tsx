import { ArrowUpRight, MapPin } from 'lucide-react'
import type { MediaItem, RegionItem } from '@/types'
import Media from './Media'
import SmartLink from './SmartLink'

/** Photo card linking to a region's page: name, capital, what it suits best and its top opportunities. */
export default function RegionCard({ region, media, capitalLabel }: { region: RegionItem; media?: MediaItem; capitalLabel: string }) {
  return <SmartLink href={`/regions/${region.slug}`} className="group relative flex h-[380px] flex-col justify-end overflow-hidden rounded-[var(--cm-radius)] md:h-[420px]">
    <Media media={media} sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" className="absolute inset-0 h-full w-full transition duration-[1.2s] ease-[var(--ease-out)] group-hover:scale-[1.06]" />
    <div className="absolute inset-0 bg-gradient-to-t from-black/95 via-black/45 to-black/5" aria-hidden />
    <div className="relative p-6 text-white md:p-7">
      <p className="mb-2 flex items-center gap-1.5 text-xs text-gold"><MapPin size={12} aria-hidden />{capitalLabel}: {region.capital}</p>
      <h3 className="font-display text-3xl">{region.name}</h3>
      <p className="mt-2 text-sm text-white/80">{region.bestFor}</p>
      <ul className="mt-4 flex flex-wrap gap-1.5">
        {region.opportunities.slice(0, 3).map(o => <li key={o} className="rounded-full border border-gold/40 bg-black/30 px-2.5 py-1 text-[11px] text-white/85 backdrop-blur-sm">{o}</li>)}
      </ul>
      <span className="absolute end-6 top-6 grid h-10 w-10 place-items-center rounded-full border border-white/25 transition group-hover:border-gold group-hover:bg-gold group-hover:text-ink md:end-7 md:top-7" aria-hidden>
        <ArrowUpRight size={16} className="flip-rtl" />
      </span>
    </div>
  </SmartLink>
}
