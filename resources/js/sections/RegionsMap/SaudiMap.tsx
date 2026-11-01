import { useId, useMemo, useState } from 'react'
import { ArrowUpRight, MapPin } from 'lucide-react'
import Media from '@/components/Media'
import SmartLink from '@/components/SmartLink'
import { MAP_H, MAP_W, regionShapes } from '@/data/saudiMap'
import { useSite } from '@/hooks/useSite'
import type { PageProps, RegionItem } from '@/types'

/**
 * Interactive map of the 13 regions, drawn like the deck: dark land, gold boundaries, a pin on the selection.
 * Regions are buttons (Tab, Enter/Space, arrow keys); a list alternative carries the same content and the
 * selection is announced politely. The map never mirrors in RTL.
 */
export default function SaudiMap({ page }: { page: PageProps }) {
  const { t } = useSite()
  const regions = page.regions.filter(region => regionShapes[region.slug])
  const [selected, setSelected] = useState(regions[0]?.slug ?? 'riyadh')
  const [hovered, setHovered] = useState<string | null>(null)
  const [industry, setIndustry] = useState('all')
  const announce = useId()

  const region = regions.find(r => r.slug === selected) ?? regions[0]
  const industries = useMemo(() => Array.from(new Set(regions.flatMap(r => r.industries))), [regions])
  const matches = (r: RegionItem) => industry === 'all' || r.industries.includes(industry)

  const onKeyDown = (e: React.KeyboardEvent, index: number) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault()
      setSelected(regions[index].slug)
      return
    }
    const step = ['ArrowRight', 'ArrowDown'].includes(e.key) ? 1 : ['ArrowLeft', 'ArrowUp'].includes(e.key) ? -1 : 0
    if (!step) return
    e.preventDefault()
    const next = regions[(index + step + regions.length) % regions.length]
    document.querySelector<SVGPathElement>(`[data-region="${next.slug}"]`)?.focus()
  }

  if (!region) return null
  const pin = regionShapes[region.slug]

  return <div className="grid items-start gap-10 lg:grid-cols-[1.25fr_1fr]">
    <div>
      <div className="mb-6 flex flex-wrap items-center gap-2" role="group" aria-label={t('filterBySector')}>
        <span className="muted me-1 text-xs">{t('filterBySector')}</span>
        {['all', ...industries].map(key => <button key={key} type="button" onClick={() => setIndustry(key)} aria-pressed={industry === key}
          className={`min-h-9 rounded-full border px-3 text-xs transition ${industry === key ? 'border-gold bg-gold text-ink' : 'hair hover:border-gold'}`}>
          {key === 'all' ? t('all') : page.industries[key] ?? key}
        </button>)}
      </div>

      <svg viewBox={`0 0 ${MAP_W} ${MAP_H}`} className="h-auto w-full" role="group" aria-label={t('mapLabel')}>
        <defs>
          <radialGradient id="cm-map-glow"><stop offset="0" stopColor="var(--cm-gold)" stopOpacity=".35" /><stop offset="1" stopColor="var(--cm-gold)" stopOpacity="0" /></radialGradient>
        </defs>
        {regions.map((r, index) => {
          const active = r.slug === selected
          const lit = active || hovered === r.slug
          return <path key={r.slug} d={regionShapes[r.slug].d} data-region={r.slug} tabIndex={0} role="button" aria-label={r.name} aria-pressed={active}
            onClick={() => setSelected(r.slug)} onKeyDown={e => onKeyDown(e, index)} onMouseEnter={() => setHovered(r.slug)} onMouseLeave={() => setHovered(null)}
            className="map-region"
            style={{
              fill: active ? 'color-mix(in srgb, var(--cm-gold) 26%, #0b0b0c)' : lit ? 'color-mix(in srgb, var(--cm-gold) 12%, #0b0b0c)' : '#0e0e0f',
              stroke: 'var(--cm-gold)', strokeOpacity: active ? 1 : 0.55, strokeWidth: active ? 2 : 1,
              opacity: matches(r) ? 1 : 0.35,
            }} />
        })}
        {regions.map(r => <text key={r.slug} x={regionShapes[r.slug].cx} y={regionShapes[r.slug].cy + (r.slug === selected ? 24 : 5)} textAnchor="middle" aria-hidden pointerEvents="none"
          className="hidden md:inline" fontSize={r.slug === selected ? 16 : 14} fontWeight={r.slug === selected ? 600 : 400} fill={r.slug === selected ? '#fff' : 'rgba(235,189,108,.8)'} style={{ fontFamily: 'var(--font-text)' }}>{r.name}</text>)}
        <g aria-hidden pointerEvents="none" transform={`translate(${pin.cx} ${pin.cy - 14})`}>
          <circle r="34" fill="url(#cm-map-glow)" />
          <path d="M0 14c-1 0-11-10.5-11-18a11 11 0 0 1 22 0c0 7.5-10 18-11 18Z" fill="var(--cm-gold)" />
          <circle cy="-4" r="4" fill="#0b0b0c" />
        </g>
      </svg>
      <p className="muted mt-3 text-[11px]">{t('mapCredit')}</p>

      <details className="hair mt-6 border-t pt-4">
        <summary className="accent flex min-h-11 cursor-pointer items-center text-sm font-semibold">{t('regionList')}</summary>
        <ul className="mt-2 grid gap-1 sm:grid-cols-2">
          {regions.map(r => <li key={r.slug}>
            <button type="button" onClick={() => setSelected(r.slug)} aria-pressed={r.slug === selected}
              className={`flex min-h-11 w-full items-start gap-2 text-start text-sm ${r.slug === selected ? 'accent' : ''}`}>
              <span className="font-semibold">{r.name}</span><span className="muted">· {r.bestFor}</span>
            </button>
          </li>)}
        </ul>
      </details>
    </div>

    <RegionPanel region={region} page={page} announceId={announce} />
  </div>
}

function RegionPanel({ region, page, announceId }: { region: RegionItem; page: PageProps; announceId: string }) {
  const { t } = useSite()
  const services = new Map(page.services.map(s => [s.slug, s]))

  return <div className="overflow-hidden rounded-[var(--cm-radius)] border border-gold/25 bg-[#0e0e0f]">
    <p id={announceId} aria-live="polite" className="sr-only">{t('selected')}: {region.name}. {t('bestFor')}: {region.bestFor}</p>
    <div className="relative h-56">
      <Media media={page.media[region.media]} sizes="(min-width: 1024px) 40vw, 100vw" className="absolute inset-0 h-full w-full" />
      <div className="absolute inset-0 bg-gradient-to-t from-black/90 via-black/25 to-transparent" aria-hidden />
      <div className="absolute bottom-0 p-6 text-white">
        <p className="eyebrow mb-2 flex items-center gap-1.5"><MapPin size={12} aria-hidden /> {t('capital')}: {region.capital}</p>
        <h3 className="font-display t-display-m">{region.name}</h3>
      </div>
    </div>
    <div className="space-y-5 p-6">
      <div><p className="eyebrow mb-1.5">{t('bestFor')}</p><p className="text-lg font-medium">{region.bestFor}</p></div>
      <p className="muted text-sm leading-relaxed">{region.description}</p>
      {region.opportunities.length > 0 && <div>
        <p className="eyebrow mb-2">{t('opportunities')}</p>
        <ul className="flex flex-wrap gap-2">{region.opportunities.map(o => <li key={o} className="hair rounded-full border px-3 py-1 text-xs">{o}</li>)}</ul>
      </div>}
      {region.services.length > 0 && <div>
        <p className="eyebrow mb-2">{t('recommended')}</p>
        <ul className="flex flex-wrap gap-x-5 gap-y-1">
          {region.services.map(slug => {
            const service = services.get(slug)
            return service && <li key={slug}><SmartLink href={`/services/${slug}`} className="inline-flex min-h-11 items-center gap-1 text-sm text-gold hover:underline">
              {service.title}<ArrowUpRight size={14} className="flip-rtl" aria-hidden />
            </SmartLink></li>
          })}
        </ul>
      </div>}
    </div>
  </div>
}
