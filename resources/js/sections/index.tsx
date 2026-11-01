import { lazy, Suspense, useId, useRef, useState, type ComponentType } from 'react'
import { ArrowUpRight, ChevronLeft, ChevronRight, Minus, Plus, Quote } from 'lucide-react'
import { Buttons, Icon, Img, Paras, SmartLink } from '@/components/ui'
import { useSite } from '@/lib/useSite'
import { groupMotion, Head, headMotion, Shell, type SectionProps } from './shared'

/* Map boundaries are ~40 KB of path data: loaded only when the section mounts (NFR-01 budget). */
const SaudiMap = lazy(() => import('./SaudiMap'))

function Hero({ s, data }: SectionProps) {
  const { L } = useSite()
  const media = s.media ? data.media[s.media] : undefined
  const zoom = s.motion.preset !== 'none'
  return <section id={s.id} data-type="hero" aria-labelledby={`${s.id}-title`} className="th-dark relative flex min-h-[88svh] items-end overflow-hidden lg:min-h-[100svh]">
    <div className="absolute inset-0 overflow-hidden" aria-hidden>
      <Img media={media} eager decorative className="h-full w-full" {...(zoom ? { 'data-motion': 'zoom' } as object : {})} />
    </div>
    {/* scrim keeps text above 4.5:1 regardless of the photo */}
    <div className="absolute inset-0" aria-hidden style={{ background: `linear-gradient(180deg, rgba(8,8,9,${Math.min(0.9, s.style.overlay / 140)}) 0%, rgba(8,8,9,${s.style.overlay / 100}) 50%, rgba(8,8,9,.94) 100%)` }} />
    <div className="wrap relative pb-14 pt-32 md:pb-24 lg:pt-44">
      <div className={`max-w-4xl ${s.style.align === 'center' ? 'mx-auto text-center' : ''}`}>
        {s.eyebrow && <p className="eyebrow mb-6 flex items-center gap-3 [animation:cm-rise_1s_var(--ease-out)_.1s_both]"><span className="h-px w-10 bg-gold" aria-hidden />{s.eyebrow}</p>}
        <h1 id={`${s.id}-title`} className="font-display t-display-xl text-white [animation:cm-rise_1.1s_var(--ease-out)_.2s_both]">{s.title}</h1>
        {s.subtitle && <p className="mt-6 max-w-2xl t-body-l text-white/80 md:mt-8 [animation:cm-rise_1.1s_var(--ease-out)_.35s_both]">{s.subtitle}</p>}
        <Buttons buttons={s.buttons} className="mt-8 text-white md:mt-10 [animation:cm-rise_1.1s_var(--ease-out)_.5s_both]" />
      </div>
    </div>
    <div className="absolute bottom-6 end-6 hidden items-center gap-3 text-[10px] uppercase tracking-[.3em] text-white/50 md:flex" aria-hidden>
      <span className="h-10 w-px bg-gradient-to-b from-transparent to-gold" />{L('scroll')}
    </div>
  </section>
}

function CardGrid({ s }: { s: SectionProps['s'] }) {
  const n = s.cards.length
  const cols = n === 4 ? 'lg:grid-cols-4' : n === 2 ? 'lg:grid-cols-2' : 'lg:grid-cols-3'
  return <div className={`swipe hair border bg-[color-mix(in_srgb,currentColor_10%,transparent)] md:grid md:grid-flow-row md:grid-cols-2 md:gap-px md:overflow-visible ${cols}`} {...groupMotion(s)}>
    {s.cards.map((c, i) => <article key={c.id} className={`th-${s.style.theme} group relative p-8 transition-colors duration-500 hover:bg-[color-mix(in_srgb,var(--cm-gold)_6%,transparent)] md:p-9`}>
      <div className="mb-10 flex items-start justify-between">
        {c.icon ? <Icon name={c.icon} className="accent" size={30} stroke={1.2} /> : <span />}
        <span className="font-display text-2xl accent opacity-60" aria-hidden>{String(i + 1).padStart(2, '0')}</span>
      </div>
      <h3 className="mb-3 text-lg font-semibold leading-snug">{c.title}</h3>
      <p className="text-sm leading-relaxed muted">{c.text}</p>
      <span className="absolute inset-x-8 bottom-0 h-px origin-[inline-start] scale-x-0 bg-gold transition-transform duration-700 group-hover:scale-x-100 rtl:origin-right" aria-hidden />
    </article>)}
  </div>
}

function WhyInvest(p: SectionProps) {
  const { s } = p
  return <Shell {...p}><div className="wrap">
    <div className="mb-14 grid items-end gap-8 lg:mb-16 lg:grid-cols-[1.2fr_1fr] lg:gap-10">
      <Head s={s} />
      <div {...headMotion(s)}><Paras text={s.body} className="leading-relaxed muted measure" /></div>
    </div>
    <CardGrid s={s} />
  </div></Shell>
}

function Journey(p: SectionProps) {
  const { s, data } = p
  const parallax = s.motion.preset === 'parallax-slow' ? { 'data-parallax': 8, 'data-intensity': s.motion.intensity } : {}
  return <Shell {...p}><div className="wrap grid items-center gap-14 lg:grid-cols-2 lg:gap-16">
    <div className="relative h-[420px] md:h-[600px]">
      <div className="absolute inset-y-0 start-0 w-[74%] overflow-hidden rounded-[var(--cm-radius)]">
        <Img media={s.media ? data.media[s.media] : undefined} sizes="(min-width: 1024px) 36vw, 74vw" className="absolute -inset-y-[10%] h-[120%] w-full" {...parallax} />
      </div>
      {s.media2 && <div className="absolute bottom-0 end-0 aspect-[4/5] w-[48%] overflow-hidden rounded-[var(--cm-radius)] border-[6px] border-ivory">
        <Img media={data.media[s.media2]} sizes="(min-width: 1024px) 24vw, 48vw" className="h-full w-full" />
      </div>}
    </div>
    <div>
      <Head s={s} />
      <div className="gold-rule my-8 w-40" data-motion="line" aria-hidden />
      <div {...headMotion(s)}><Paras text={s.body} className="mb-4 t-body-l muted measure" /><Buttons buttons={s.buttons} className="mt-8" /></div>
    </div>
  </div></Shell>
}

function Countries(p: SectionProps) {
  const { s, data } = p
  return <Shell {...p}><div className="wrap grid items-center gap-14 lg:grid-cols-[1fr_1.1fr] lg:gap-16">
    <div><Head s={s} /><div {...headMotion(s)}><Paras text={s.body} className="mt-8 leading-relaxed muted measure" /></div></div>
    <div className="relative">
      {s.media && <div className="pointer-events-none absolute -inset-10 opacity-25" aria-hidden><Img media={data.media[s.media]} decorative className="h-full w-full rounded-full" /></div>}
      <ul className="hair relative grid grid-cols-2 gap-px border bg-white/5 sm:grid-cols-3" {...groupMotion(s)}>
        {data.countries.map(c => <li key={c.code} className="th-dark flex min-h-14 items-center gap-3 px-5 py-4 text-sm">
          <span className="w-5 font-mono text-[10px] uppercase text-gold" aria-hidden>{c.code}</span>{c.name}
        </li>)}
      </ul>
    </div>
  </div></Shell>
}

function Sectors(p: SectionProps) {
  const { s, data } = p
  return <Shell {...p}><div className="wrap">
    <div className="mb-14 grid items-end gap-8 lg:grid-cols-[1.2fr_1fr] lg:gap-10"><Head s={s} /><div {...headMotion(s)}><Paras text={s.body} className="leading-relaxed muted measure" /></div></div>
    <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" {...groupMotion(s)}>
      {s.cards.map((c, i) => <li key={c.id} className="group relative h-[300px] overflow-hidden rounded-[var(--cm-radius)] md:h-[360px]">
        <Img media={c.media ? data.media[c.media] : undefined} sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" className="absolute inset-0 h-full w-full transition duration-[1.1s] ease-[var(--ease-out)] group-hover:scale-[1.06]" />
        <div className="absolute inset-0 bg-gradient-to-t from-black/90 via-black/35 to-black/5" aria-hidden />
        <div className="absolute inset-x-0 bottom-0 p-7 text-white">
          <span className="font-display text-xl text-gold" aria-hidden>{String(i + 1).padStart(2, '0')}</span>
          <h3 className="mt-2 text-xl font-semibold">{c.title}</h3>
          <p className="mt-2 text-sm text-white/75 lg:max-h-0 lg:overflow-hidden lg:opacity-0 lg:transition-all lg:duration-500 lg:group-hover:max-h-24 lg:group-hover:opacity-100 lg:group-focus-within:max-h-24 lg:group-focus-within:opacity-100">{c.text}</p>
        </div>
      </li>)}
    </ul>
  </div></Shell>
}

/* Values like "1,000+" render as text first (no JS, reduced motion); the counter preset animates the number. */
function StatValue({ value, animate }: { value: string; animate: boolean }) {
  const m = value.match(/^(\d[\d,]*)(\D*)$/)
  if (!m || !animate) return <span dir="ltr">{value}</span>
  return <span dir="ltr" data-counter={m[1].replace(/,/g, '')} data-suffix={m[2]}>{value}</span>
}

function TrackRecord(p: SectionProps) {
  const { s, data } = p
  return <Shell {...p}><div className="wrap">
    <div className="mb-14"><Head s={s} center /></div>
    <dl className={`hair mx-auto grid max-w-4xl border-y ${data.stats.length > 2 ? 'grid-cols-2 md:grid-cols-4' : 'grid-cols-2'}`}>
      {data.stats.map(x => <div key={x.key} className="hair flex flex-col-reverse border-e px-4 py-10 text-center last:border-e-0">
        <dt className="mt-4 text-xs font-semibold uppercase tracking-[.16em] text-white/75">{x.label}</dt>
        <dd className="font-display text-6xl leading-none text-gold md:text-8xl"><StatValue value={x.value} animate={s.motion.preset === 'counter'} /></dd>
      </div>)}
    </dl>
  </div></Shell>
}

function Services(p: SectionProps) {
  const { s, data } = p
  const { L } = useSite()
  const list = data.services.slice(0, s.limit ?? 11)
  return <Shell {...p}><div className="wrap">
    <div className="mb-14 flex flex-wrap items-end justify-between gap-6"><Head s={s} /><Buttons buttons={s.buttons} className="hidden md:flex" /></div>
    <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" {...groupMotion(s)}>
      {list.map((sv, i) => <li key={sv.slug} className={i >= 4 ? 'hidden sm:block' : ''}>
        <SmartLink href={`/services/${sv.slug}`} className="card-s group flex h-full flex-col overflow-hidden rounded-[var(--cm-radius)] transition duration-500 hover:-translate-y-1 hover:border-[var(--cm-gold-deep)]">
          <div className="relative h-48 overflow-hidden">
            <Img media={data.media[sv.media]} sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" className="h-full w-full transition duration-[1.1s] group-hover:scale-[1.06]" />
            <span className="absolute start-4 top-4 grid h-11 w-11 place-items-center rounded-full bg-ink/80 text-gold backdrop-blur" aria-hidden><Icon name={sv.icon} size={19} /></span>
          </div>
          <div className="flex flex-1 flex-col p-7">
            <p className="eyebrow mb-2" aria-hidden>{String(sv.order).padStart(2, '0')}</p>
            <h3 className="mb-2 text-xl font-semibold">{sv.title}</h3>
            <p className="line-clamp-3 text-sm leading-relaxed muted">{sv.summary}</p>
            <span className="accent mt-auto inline-flex items-center gap-2 pt-6 text-sm font-semibold">{sv.cta}<ArrowUpRight size={15} className="flip-rtl" aria-hidden /></span>
          </div>
        </SmartLink>
      </li>)}
    </ul>
    <SmartLink href="/services" className="btn btn-outline mt-8 w-full md:hidden">{L('allServices')}</SmartLink>
  </div></Shell>
}

function MapSection(p: SectionProps) {
  const { s, data } = p
  return <Shell {...p}><div className="wrap">
    <div className="mb-14 grid items-end gap-8 lg:grid-cols-[1.2fr_1fr] lg:gap-10"><Head s={s} /><div {...headMotion(s)}><Paras text={s.body} className="leading-relaxed muted measure" /></div></div>
    <Suspense fallback={<div className="aspect-[1000/816] w-full lg:w-[55%]" aria-hidden />}><SaudiMap data={data} /></Suspense>
  </div></Shell>
}

function Faq(p: SectionProps) {
  const { s, data } = p
  const [open, setOpen] = useState<number | null>(data.faqs[0]?.id ?? null)
  const uid = useId()
  return <Shell {...p}><div className="wrap grid gap-14 lg:grid-cols-[1fr_1.6fr]">
    <div><Head s={s} /><Buttons buttons={s.buttons} className="mt-8" /></div>
    <div className="hair divide-y border-y" {...headMotion(s)}>
      {data.faqs.map(f => {
        const o = open === f.id
        return <div key={f.id} className="hair">
          <h3>
            <button type="button" id={`${uid}-q${f.id}`} aria-expanded={o} aria-controls={`${uid}-a${f.id}`} onClick={() => setOpen(o ? null : f.id)}
              className="flex min-h-16 w-full items-center justify-between gap-6 py-5 text-start text-lg font-medium">
              <span>{f.question}</span>
              <span className={`grid h-9 w-9 shrink-0 place-items-center rounded-full border transition ${o ? 'border-ink bg-ink text-ivory' : 'border-[#CFC8B8]'}`} aria-hidden>{o ? <Minus size={14} /> : <Plus size={14} />}</span>
            </button>
          </h3>
          <div id={`${uid}-a${f.id}`} role="region" aria-labelledby={`${uid}-q${f.id}`} hidden={!o} className="pb-6">
            <p className="measure leading-relaxed muted">{f.answer}</p>
          </div>
        </div>
      })}
    </div>
  </div></Shell>
}

function Cta(p: SectionProps) {
  const { s } = p
  const c = s.style.align === 'center'
  return <Shell {...p}><div className={`wrap ${c ? 'text-center' : ''}`}><div className={`max-w-3xl ${c ? 'mx-auto' : ''}`}>
    {s.title && <h2 id={`${s.id}-title`} className="font-display t-display-xl" {...headMotion(s)}>{s.title}</h2>}
    {s.body && <p className={`mt-6 t-body-l muted measure ${c ? 'mx-auto' : ''}`}>{s.body}</p>}
    <Buttons buttons={s.buttons} className={`mt-10 ${c ? 'justify-center' : ''}`} />
  </div></div></Shell>
}

/* Static grid on desktop; on mobile a swipe row with keyboard-operable previous/next (never auto-advances). */
function Testimonials(p: SectionProps) {
  const { s, data } = p
  const { L } = useSite()
  const row = useRef<HTMLUListElement>(null)
  const go = (dir: 1 | -1) => {
    const el = row.current; if (!el) return
    const rtl = document.documentElement.dir === 'rtl'
    el.scrollBy({ left: dir * (rtl ? -1 : 1) * el.clientWidth * 0.85, behavior: 'smooth' })
  }
  return <Shell {...p}><div className="wrap">
    <div className="mb-14"><Head s={s} center /></div>
    <ul ref={row} className="swipe md:grid md:grid-flow-row md:grid-cols-3 md:gap-6 md:overflow-visible" {...groupMotion(s)}>
      {data.testimonials.map(t => <li key={t.id} className="card-s flex flex-col rounded-[var(--cm-radius)] p-8">
        <Quote className="mb-6 text-gold flip-rtl" size={28} strokeWidth={1.2} aria-hidden />
        <blockquote className="flex-1 font-display text-xl leading-relaxed">“{t.quote}”</blockquote>
        <div className="hair mt-8 border-t pt-5">
          <p className="text-sm font-semibold">{t.author ?? L('clientReview')}</p>
          {t.context && <p className="text-xs muted">{t.context}</p>}
          {!t.author && <p className="mt-3 text-[11px] muted">{L('unverified')}</p>}
        </div>
      </li>)}
    </ul>
    <div className="mt-6 flex justify-center gap-3 md:hidden">
      <button type="button" onClick={() => go(-1)} aria-label={L('prev')} className="hair grid h-11 w-11 place-items-center rounded-full border"><ChevronLeft size={18} className="flip-rtl" aria-hidden /></button>
      <button type="button" onClick={() => go(1)} aria-label={L('next')} className="hair grid h-11 w-11 place-items-center rounded-full border"><ChevronRight size={18} className="flip-rtl" aria-hidden /></button>
    </div>
  </div></Shell>
}

export const SECTIONS: Record<string, ComponentType<SectionProps>> = {
  hero: Hero, whyInvest: WhyInvest, journey: Journey, countries: Countries, sectors: Sectors,
  trackRecord: TrackRecord, services: Services, map: MapSection, faq: Faq, cta: Cta, testimonials: Testimonials,
}
