import { lazy, Suspense, useRef } from 'react'
import { Head } from '@inertiajs/react'
import Breadcrumbs from '@/components/Breadcrumbs'
import RegionCard from '@/components/RegionCard'
import ServiceCard from '@/components/ServiceCard'
import TextBlock from '@/components/TextBlock'
import { useSite } from '@/hooks/useSite'
import SiteLayout from '@/layouts/SiteLayout'
import { useMotion } from '@/motion/useMotion'
import ConsultationCta from '@/sections/ConsultationCta'
import Hero from '@/sections/Hero/Hero'
import type { PageProps, RegionPageProps, Section } from '@/types'

// The map outline is ~40 KB, so it loads after the page.
const RegionLocator = lazy(() => import('@/components/RegionLocator'))

const base = (id: string, type: Section['type'], extra: Partial<Section>): Section => ({
  id, type, buttons: [], cards: [], slides: [],
  style: { theme: 'dark', overlay: 55, padding: 'lg', align: 'start' },
  motion: { preset: 'fade-up', intensity: 100, delay: 0 },
  ...extra,
})

/**
 * Region detail (FR-051): hero photograph, where the opportunity lies, leading sectors, location on the map,
 * recommended business solutions, three more regions and a consultation call to action.
 */
export default function RegionPage(props: RegionPageProps) {
  const { t, locale } = useSite()
  const scope = useRef<HTMLDivElement>(null)
  useMotion(scope, `region-${props.region.slug}-${locale.code}`)
  const { region } = props
  const consultation = `/consultation?region=${region.slug}`

  // The shared section renderers read from a page-shaped object.
  const page = { ...props, services: props.related, regions: props.otherRegions, countries: [], testimonials: [], faqs: [], sections: [] } as unknown as PageProps

  const hero = base(`region-${region.slug}`, 'hero', {
    eyebrow: `${t('capital')}: ${region.capital}`, title: region.name, subtitle: region.bestFor, media: region.media,
    buttons: [{ label: t('discussRegion'), href: consultation, style: 'gold' }, { label: t('allRegions'), href: '/regions', style: 'outline' }],
    style: { theme: 'dark', overlay: 55, padding: 'md', align: 'start' }, motion: { preset: 'zoom-in', intensity: 100, delay: 0 },
  })
  const cta = base('region-cta', 'cta', {
    title: t('discussRegion'), body: t('ctaBody'),
    buttons: [{ label: t('bookConsultation'), href: consultation, style: 'gold' }],
    style: { theme: 'dark', overlay: 74, padding: 'lg', align: 'center', bgMedia: 'about-kingdom-centre' },
    motion: { preset: 'reveal-mask', intensity: 100, delay: 0 },
  })

  return <SiteLayout>
    <Head>
      <title>{props.seo.title}</title>
      <meta name="description" content={props.seo.description} />
    </Head>
    <div ref={scope}>
      <Hero section={hero} page={page} />
      <Breadcrumbs trail={[{ label: props.regionsTitle, href: '/regions' }, { label: region.name }]} />

      <section aria-labelledby="opportunity-title" className="th-dark pad-lg">
        <div className="wrap grid gap-12 lg:grid-cols-[1.2fr_1fr] lg:gap-20">
          <div data-motion="fade-up">
            <p className="eyebrow mb-5 flex items-center gap-3"><span className="h-px w-8 bg-current" aria-hidden />{t('whereOpportunity')}</p>
            <h2 id="opportunity-title" className="font-display t-display-l">{region.bestFor}</h2>
            <div className="gold-rule my-8 w-32" data-motion="line" aria-hidden />
            <div className="space-y-4"><TextBlock text={region.description} className="t-body-l muted measure" /></div>
            {region.opportunities.length > 0 && <>
              <h3 className="eyebrow mb-4 mt-10">{t('opportunities')}</h3>
              <ul className="flex flex-wrap gap-2">
                {region.opportunities.map(o => <li key={o} className="rounded-full border border-gold/40 px-4 py-2 text-sm">{o}</li>)}
              </ul>
            </>}
          </div>
          <aside className="self-start rounded-[8px] border border-gold/30 bg-gradient-to-b from-white/[.04] to-transparent p-7 md:p-9" data-motion="fade-up">
            <div className="mx-auto max-w-[420px]">
              <Suspense fallback={<div className="aspect-[1000/816]" />}>
                <RegionLocator slug={region.slug} label={`${region.name}: ${t('regionLocation')}`} />
              </Suspense>
            </div>
            {region.industries.length > 0 && <>
              <h3 className="eyebrow mb-4 mt-8">{t('leadingSectors')}</h3>
              <ul className="grid gap-2 sm:grid-cols-2">
                {region.industries.map(i => <li key={i} className="flex items-center gap-3 border-b border-white/[.06] py-2.5 text-sm">
                  <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-gold" aria-hidden />{props.industries[i] ?? i}
                </li>)}
              </ul>
            </>}
          </aside>
        </div>
      </section>

      {props.related.length > 0 && <section aria-labelledby="support-title" className="th-charcoal pad-lg">
        <div className="wrap">
          <p className="eyebrow mb-5">{t('recommended')}</p>
          <h2 id="support-title" className="font-display t-display-l mb-12">{t('regionServices')}</h2>
          <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-motion="stagger">
            {props.related.map(item => <li key={item.slug}><ServiceCard service={item} media={props.media[item.media]} /></li>)}
          </ul>
        </div>
      </section>}

      <section aria-labelledby="more-title" className="th-dark pad-lg">
        <div className="wrap">
          <p className="eyebrow mb-5">{props.regionsTitle}</p>
          <h2 id="more-title" className="font-display t-display-l mb-12">{t('otherRegions')}</h2>
          <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-motion="stagger">
            {props.otherRegions.map(r => <li key={r.slug}><RegionCard region={r} media={props.media[r.media]} capitalLabel={t('capital')} /></li>)}
          </ul>
        </div>
      </section>

      <ConsultationCta section={cta} page={page} />
    </div>
  </SiteLayout>
}
