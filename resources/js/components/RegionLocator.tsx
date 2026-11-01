import { MAP_H, MAP_W, regionShapes } from '@/data/saudiMap'

/** Small outline of the Kingdom with one region highlighted (decorative; the region is named in the text). */
export default function RegionLocator({ slug, label }: { slug: string; label: string }) {
  const target = regionShapes[slug]
  return <svg viewBox={`0 0 ${MAP_W} ${MAP_H}`} role="img" aria-label={label} className="h-auto w-full">
    {Object.entries(regionShapes).map(([key, shape]) => <path key={key} d={shape.d}
      className={key === slug ? 'fill-gold stroke-gold' : 'fill-white/[.05] stroke-white/25'} strokeWidth={1.2} />)}
    {target && <g>
      <circle cx={target.cx} cy={target.cy} r={22} className="fill-ink/40 stroke-ink" strokeWidth={3} />
      <circle cx={target.cx} cy={target.cy} r={7} className="fill-ink" />
    </g>}
  </svg>
}
