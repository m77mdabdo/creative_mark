import { useId, useMemo, useState } from 'react'
import { ArrowUpRight, MapPin } from 'lucide-react'
import { MAP_H, MAP_W, regionShapes } from '@/data/saudiMap'
import type { HomeProps } from '@/types'
import { Img, SmartLink } from '@/components/ui'
import { useSite } from '@/lib/useSite'

/**
 * Interactive map of the 13 regions (FR-017). Regions are buttons (Tab + Enter/Space, arrow keys move between
 * them); a list alternative carries the same content, and the selection is announced in a polite live region.
 * The map never mirrors in RTL.
 */
export default function SaudiMap({ data }: { data: HomeProps }) {
  const { L } = useSite()
  const regions = data.regions.filter(r => regionShapes[r.slug])
  const [sel, setSel] = useState(regions[0]?.slug ?? 'riyadh')
  const [hover, setHover] = useState<string | null>(null)
  const [ind, setInd] = useState('all')
  const live = useId()
  const r = regions.find(x => x.slug === sel) ?? regions[0]
  const inds = useMemo(() => Array.from(new Set(regions.flatMap(x => x.industries))), [regions])
  const match = (slug: string) => ind === 'all' || !!regions.find(x => x.slug === slug)?.industries.includes(ind)
  const services = new Map(data.services.map(s => [s.slug, s]))

  const onKey = (e: React.KeyboardEvent, i: number) => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setSel(regions[i].slug); return }
    const step = e.key === 'ArrowRight' || e.key === 'ArrowDown' ? 1 : e.key === 'ArrowLeft' || e.key === 'ArrowUp' ? -1 : 0
    if (!step) return
    e.preventDefault()
    const next = (i + step + regions.length) % regions.length
    document.querySelector<SVGPathElement>(`[data-region="${regions[next].slug}"]`)?.focus()
  }

  if (!r) return null
  return <div className="grid items-start gap-10 lg:grid-cols-[1.25fr_1fr]">
    <div>
      <div className="mb-5 flex flex-wrap items-center gap-2" role="group" aria-label={L('filterBySector')}>
        <span className="me-1 text-xs muted">{L('filterBySector')}</span>
        {['all', ...inds].map(i => <button key={i} type="button" onClick={() => setInd(i)} aria-pressed={ind === i}
          className={`min-h-9 rounded-full border px-3 text-xs transition ${ind === i ? 'border-gold bg-gold text-ink' : 'hair hover:border-gold'}`}>
          {i === 'all' ? L('all') : data.industries[i] ?? i}
        </button>)}
      </div>
      <svg viewBox={`0 0 ${MAP_W} ${MAP_H}`} className="h-auto w-full" role="group" aria-label={L('mapLabel')}>
        <defs><linearGradient id="cm-gsel" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stopColor="var(--cm-gold)" /><stop offset="1" stopColor="var(--cm-gold-deep)" /></linearGradient></defs>
        {regions.map((reg, i) => {
          const s = regionShapes[reg.slug]; const on = reg.slug === sel; const m = match(reg.slug); const hv = hover === reg.slug
          return <path key={reg.slug} d={s.d} data-region={reg.slug} tabIndex={0} role="button" aria-label={reg.name} aria-pressed={on}
            onClick={() => setSel(reg.slug)} onKeyDown={e => onKey(e, i)} onMouseEnter={() => setHover(reg.slug)} onMouseLeave={() => setHover(null)}
            className="map-region"
            style={{ fill: on ? 'url(#cm-gsel)' : hv ? 'color-mix(in srgb, var(--cm-gold) 45%, #1d1d1f)' : m ? 'color-mix(in srgb, var(--cm-gold) 18%, #1a1a1c)' : '#141415', stroke: on ? '#FBEBC8' : 'color-mix(in srgb, var(--cm-gold) 55%, transparent)', strokeWidth: on ? 2 : 0.9, opacity: m ? 1 : 0.45 }} />
        })}
        {regions.map(reg => {
          const s = regionShapes[reg.slug]; const on = reg.slug === sel
          return <g key={reg.slug} pointerEvents="none" aria-hidden>
            <circle cx={s.cx} cy={s.cy} r={on ? 6 : 3.5} fill={on ? '#0B0B0C' : 'var(--cm-gold)'} />
            <text x={s.cx} y={s.cy - 11} textAnchor="middle" fontSize={on ? 19 : 14} fontWeight={on ? 700 : 500} fill={on ? '#0B0B0C' : '#E9E2D2'} stroke={on ? 'none' : '#0B0B0C'} strokeWidth={3} style={{ paintOrder: 'stroke', fontFamily: 'var(--font-text)' }} className="hidden md:inline">{reg.name}</text>
          </g>
        })}
      </svg>
      <p className="mt-3 text-[11px] muted">{L('mapCredit')}</p>
      <details className="mt-6 border-t hair pt-4">
        <summary className="flex min-h-11 cursor-pointer items-center text-sm font-semibold accent">{L('regionList')}</summary>
        <ul className="mt-2 grid gap-1 sm:grid-cols-2">
          {regions.map(reg => <li key={reg.slug}><button type="button" onClick={() => setSel(reg.slug)} aria-pressed={reg.slug === sel}
            className={`flex min-h-11 w-full items-start gap-2 text-start text-sm ${reg.slug === sel ? 'accent' : ''}`}>
            <span className="font-semibold">{reg.name}</span><span className="muted">· {reg.bestFor}</span>
          </button></li>)}
        </ul>
      </details>
    </div>

    <div className="card-s overflow-hidden rounded-[var(--cm-radius)]">
      <p id={live} aria-live="polite" className="sr-only">{L('selected')}: {r.name}. {L('bestFor')}: {r.bestFor}</p>
      <div className="relative h-56">
        <Img media={data.media[r.media]} sizes="(min-width: 1024px) 40vw, 100vw" className="absolute inset-0 h-full w-full" />
        <div className="absolute inset-0 bg-gradient-to-t from-black/85 via-black/25 to-transparent" />
        <div className="absolute bottom-0 p-6 text-white">
          <p className="eyebrow mb-2 flex items-center gap-1.5"><MapPin size={12} aria-hidden /> {L('capital')}: {r.capital}</p>
          <h3 className="font-display t-display-m"><span className="sr-only">{L('selected')}: </span>{r.name}</h3>
        </div>
      </div>
      <div className="space-y-5 p-6">
        <div><p className="eyebrow mb-1.5">{L('bestFor')}</p><p className="text-lg font-medium">{r.bestFor}</p></div>
        <p className="text-sm leading-relaxed muted">{r.description}</p>
        {r.opportunities.length > 0 && <div><p className="eyebrow mb-2">{L('opportunities')}</p><ul className="flex flex-wrap gap-2">{r.opportunities.map((o, i) => <li key={i} className="hair rounded-full border px-3 py-1 text-xs">{o}</li>)}</ul></div>}
        {r.services.length > 0 && <div><p className="eyebrow mb-2">{L('recommended')}</p><ul className="flex flex-wrap gap-x-5 gap-y-1">
          {r.services.map(slug => { const s = services.get(slug); return s && <li key={slug}><SmartLink href={`/services/${slug}`} className="inline-flex min-h-11 items-center gap-1 text-sm text-gold hover:underline">{s.title}<ArrowUpRight size={14} className="flip-rtl" aria-hidden /></SmartLink></li> })}
        </ul></div>}
      </div>
    </div>
  </div>
}
