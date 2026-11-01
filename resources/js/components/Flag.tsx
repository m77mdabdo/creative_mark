/** Circular country flag (circle-flags, MIT). Decorative: the country name is always shown next to it. */
export default function Flag({ code, size = 40 }: { code: string; size?: number }) {
  return <img src={`/flags/${code}.svg`} alt="" width={size} height={size} loading="lazy" className="shrink-0 rounded-full ring-1 ring-white/15" />
}
