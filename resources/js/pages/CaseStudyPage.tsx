import { lazy, Suspense, useEffect, useRef, useState } from 'react'
import { Head } from '@inertiajs/react'
import { Check, Info, MapPin } from 'lucide-react'
import Breadcrumbs from '@/components/Breadcrumbs'
import CaseCard from '@/components/CaseCard'
import Flag from '@/components/Flag'
import Media from '@/components/Media'
import ServiceCard from '@/components/ServiceCard'
import SmartLink from '@/components/SmartLink'
import TextBlock from '@/components/TextBlock'
import { useSite } from '@/hooks/useSite'
import SiteLayout from '@/layouts/SiteLayout'
import { prefersReducedMotion } from '@/motion/gsap'
import { useMotion } from '@/motion/useMotion'
import ConsultationCta from '@/sections/ConsultationCta'
import type { CaseStudyPageProps, PageProps, Section } from '@/types'

const RegionLocator = lazy(() => import('@/components/RegionLocator'))

const CHAPTERS = ['start', 'steps', 'delivered', 'leads'] as const

/** Fills the timeline line as the reader scrolls through the steps (static and full with reduced motion). */
function useTimelineProgress(ref: React.RefObject<HTMLElement | null>) {
  const [progress, setProgress] = useState(1)
  useEffect(() => {
    if (prefersReducedMotion()) return
    let frame = 0
    const measure = () => {
      frame = 0
      const el = ref.current
      if (!el) return
      const r = el.getBoundingClientRect()
      const anchor = window.innerHeight * 0.6
      setProgress(Math.min(1, Math.max(0, (anchor - r.top) / r.height)))
    }
    const onScroll = () => { if (!frame) frame = requestAnimationFrame(measure) }
    measure()
    window.addEventListener('scroll', onScroll, { passive: true })
    window.addEventListener('resize', onScroll)
    return () => { window.removeEventListener('scroll', onScroll); window.removeEventListener('resize', onScroll); cancelAnimationFrame(frame) }
  }, [ref])
  return progress
}

/** Highlights the chapter currently in view in the side index. */
function useActiveChapter(ids: readonly string[]) {
  const [active, setActive] = useState(ids[0])
  useEffect(() => {
    const observer = new IntersectionObserver(entries => {
      const visible = entries.filter(e => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0]
      if (visible) setActive(visible.target.id.replace('case-', ''))
    }, { rootMargin: '-35% 0px -55% 0px' })
    ids.forEach(id => { const el = document.getElementById(`case-${id}`); if (el) observer.observe(el) })
    return () => observer.disconnect()
  }, [ids])
  return active
}

/**
 * A case study told as four chapters (starting point, steps, delivered, where it leads) beside a sticky index,
 * with an "at a glance" bar, the region on the map, the services used and more cases.
 */
export default function CaseStudyPage(props: CaseStudyPageProps) {
  const { t, locale } = useSite()
  const scope = useRef<HTMLDivElement>(null)
  useMotion(scope, `case-${props.caseStudy.slug}-${locale.code}`)
  const { caseStudy: c, region } = props
  const country = props.countries.find(x => x.code === c.origin)
  const timeline = useRef<HTMLOListElement>(null)
  const progress = useTimelineProgress(timeline)
  const active = useActiveChapter(CHAPTERS)
  const page = { ...props, services: props.related, testimonials: [], faqs: [], sections: [] } as unknown as PageProps
  const labels: Record<(typeof CHAPTERS)[number], string> = { start: t('startingPoint'), steps: t('ourSteps'), delivered: t('whatDelivered'), leads: t('whereItLeads') }
  const [firstParagraph, ...rest] = c.challenge.split(/\n{2,}/)

  const cta: Section = {
    id: 'case-cta', type: 'cta', cards: [], slides: [],
    title: t('ctaTitle'), body: t('ctaBody'),
    buttons: [{ label: t('bookConsultation'), href: '/consultation', style: 'gold' }],
    style: { theme: 'dark', overlay: 74, padding: 'lg', align: 'center', bgMedia: 'case-dubai' },
    motion: { preset: 'reveal-mask', intensity: 100, delay: 0 },
  }

  return <SiteLayout>
    <Head>
      <title>{props.seo.title}</title>
      <meta name="description" content={props.seo.description} />
    </Head>
    <div ref={scope}>
      <header className="th-dark relative flex min-h-[86svh] items-end overflow-hidden">
        <Media media={props.media[c.media]} eager sizes="100vw" className="absolute inset-0 h-full w-full animate-[cm-kenburns_14s_ease-out_both] motion-reduce:animate-none" />
        <div className="absolute inset-0 bg-gradient-to-t from-ink via-black/65 to-black/25" aria-hidden />
        <div className="wrap relative pb-14 pt-36">
          <p className="mb-6 flex flex-wrap items-center gap-3 text-sm text-white/80">
            {c.illustrative && <span className="rounded-full border border-white/40 bg-black/40 px-3 py-1 text-xs backdrop-blur-sm">{t('illustrative')}</span>}
            {country && <span className="flex items-center gap-2"><Flag code={country.code} size={22} />{c.client}</span>}
          </p>
          <h1 className="font-display t-display-xl max-w-5xl text-white">{c.title}</h1>
          <p className="t-body-l mt-6 max-w-3xl text-white/80">{c.summary}</p>
        </div>
      </header>
      <Breadcrumbs trail={[{ label: props.casesTitle, href: '/case-studies' }, { label: c.title }]} />

      {/* At a glance */}
      <section aria-label={t('atAGlance')} className="th-charcoal border-b border-white/10">
        <dl className="wrap grid grid-cols-2 divide-white/10 md:grid-cols-4 md:divide-x rtl:md:divide-x-reverse">
          {[
            [t('sector'), props.industries[c.sector] ?? c.sector],
            [t('clientFrom'), country?.name ?? c.origin],
            [t('regionLabel'), region?.name ?? c.region],
            [t('legalForm'), c.entity ?? '—'],
          ].map(([label, value]) => <div key={label} className="flex flex-col-reverse gap-1 py-6 md:px-6 md:first:ps-0">
            <dt className="text-xs uppercase tracking-[.14em] text-white/50">{label}</dt>
            <dd className="text-lg leading-snug">{value}</dd>
          </div>)}
        </dl>
      </section>

      <section className="th-dark pad-lg">
        <div className="wrap grid gap-12 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-20">
          <nav aria-label={t('atAGlance')} className="hidden lg:block">
            <ol className="sticky top-28 space-y-1 border-s border-white/10">
              {CHAPTERS.map((id, i) => <li key={id}>
                <a href={`#case-${id}`} className={`-ms-px block border-s-2 py-2 ps-5 text-sm transition ${active === id ? 'border-gold text-white' : 'border-transparent text-white/50 hover:text-white'}`}>
                  <span className="me-2 font-display text-gold">{String(i + 1).padStart(2, '0')}</span>{labels[id]}
                </a>
              </li>)}
            </ol>
          </nav>

          <div className="min-w-0 space-y-24">
            {c.illustrative && <p className="flex gap-3 rounded-[var(--cm-radius)] border border-white/15 bg-white/[.03] p-4 text-sm text-white/70"><Info size={18} className="mt-0.5 shrink-0 text-gold" aria-hidden />{t('illustrativeNote')}</p>}

            <article id="case-start" className="scroll-mt-28" aria-labelledby="case-start-title">
              <p className="eyebrow mb-4">01</p>
              <h2 id="case-start-title" className="font-display t-display-l mb-8">{labels.start}</h2>
              <p className="font-display text-2xl leading-snug text-white md:text-3xl measure" data-motion="fade-up">{firstParagraph}</p>
              {rest.length > 0 && <div className="mt-6 space-y-4"><TextBlock text={rest.join('\n\n')} className="t-body-l muted measure" /></div>}
            </article>

            <article id="case-steps" className="scroll-mt-28" aria-labelledby="case-steps-title">
              <p className="eyebrow mb-4">02</p>
              <h2 id="case-steps-title" className="font-display t-display-l mb-10">{labels.steps}</h2>
              <ol ref={timeline} className="relative space-y-10 ps-12 md:ps-16">
                <span className="absolute bottom-2 start-[15px] top-2 w-px bg-white/15 md:start-[19px]" aria-hidden />
                <span className="absolute start-[15px] top-2 w-px origin-top bg-gold md:start-[19px]" style={{ height: `calc((100% - 1rem) * ${progress})` }} aria-hidden />
                {c.steps.map((s, i) => {
                  const reached = progress >= (i + 0.2) / c.steps.length
                  return <li key={s.title} className="relative">
                    <span className={`absolute -start-12 top-0 grid h-8 w-8 place-items-center rounded-full border text-xs font-semibold transition duration-500 md:-start-16 md:h-10 md:w-10 ${reached ? 'border-gold bg-gold text-ink' : 'border-white/25 bg-ink text-white/60'}`} aria-hidden>{i + 1}</span>
                    <p className="mb-1 text-xs text-gold">{t('step')} {i + 1}</p>
                    <h3 className="mb-2 text-xl font-medium">{s.title}</h3>
                    <p className="text-white/70 measure">{s.text}</p>
                  </li>
                })}
              </ol>
            </article>

            <article id="case-delivered" className="scroll-mt-28" aria-labelledby="case-delivered-title">
              <p className="eyebrow mb-4">03</p>
              <h2 id="case-delivered-title" className="font-display t-display-l mb-10">{labels.delivered}</h2>
              <ul className="grid gap-3 sm:grid-cols-2" data-motion="stagger">
                {c.delivered.map(d => <li key={d} className="flex items-start gap-3 rounded-[var(--cm-radius)] border border-white/10 bg-white/[.02] p-5">
                  <span className="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-gold/15 text-gold" aria-hidden><Check size={15} /></span>
                  <span>{d}</span>
                </li>)}
              </ul>
            </article>

            <article id="case-leads" className="scroll-mt-28" aria-labelledby="case-leads-title">
              <p className="eyebrow mb-4">04</p>
              <h2 id="case-leads-title" className="font-display t-display-l mb-8">{labels.leads}</h2>
              <blockquote className="border-s-2 border-gold ps-6 font-display text-2xl leading-snug text-white md:text-4xl" data-motion="fade-up">{c.outcome}</blockquote>
            </article>
          </div>
        </div>
      </section>

      <section aria-labelledby="case-context-title" className="th-charcoal pad-lg">
        <div className="wrap grid gap-12 lg:grid-cols-[1fr_2fr] lg:gap-16">
          {region && <div>
            <h2 id="case-context-title" className="eyebrow mb-6">{t('regionLabel')}</h2>
            <div className="rounded-[var(--cm-radius)] border border-gold/25 p-6">
              <Suspense fallback={<div className="aspect-[1000/816]" />}><RegionLocator slug={region.slug} label={`${region.name}: ${t('regionLocation')}`} /></Suspense>
              <SmartLink href={`/regions/${region.slug}`} className="mt-5 flex items-center gap-2 text-lg hover:text-gold"><MapPin size={16} className="text-gold" aria-hidden />{region.name}</SmartLink>
              <p className="mt-1 text-sm text-white/60">{region.bestFor}</p>
            </div>
          </div>}
          {props.related.length > 0 && <div>
            <h2 className="eyebrow mb-6">{t('servicesUsed')}</h2>
            <ul className="grid gap-5 sm:grid-cols-2" data-motion="stagger">
              {props.related.slice(0, 4).map(s => <li key={s.slug}><ServiceCard service={s} media={props.media[s.media]} /></li>)}
            </ul>
          </div>}
        </div>
      </section>

      {props.otherCases.length > 0 && <section aria-labelledby="more-cases-title" className="th-dark pad-lg">
        <div className="wrap">
          <h2 id="more-cases-title" className="font-display t-display-l mb-10">{t('moreCases')}</h2>
          <ul className="grid gap-6 md:grid-cols-2" data-motion="stagger">
            {props.otherCases.map(o => <li key={o.slug}><CaseCard item={o} media={props.media[o.media]} industries={props.industries} countries={props.countries} regions={props.regions} compact /></li>)}
          </ul>
        </div>
      </section>}

      <ConsultationCta section={cta} page={page} />
    </div>
  </SiteLayout>
}
