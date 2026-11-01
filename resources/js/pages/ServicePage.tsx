import { useRef } from 'react'
import { Head } from '@inertiajs/react'
import { Check } from 'lucide-react'
import Breadcrumbs from '@/components/Breadcrumbs'
import ServiceCard from '@/components/ServiceCard'
import TextBlock from '@/components/TextBlock'
import { useSite } from '@/hooks/useSite'
import SiteLayout from '@/layouts/SiteLayout'
import { useMotion } from '@/motion/useMotion'
import ConsultationCta from '@/sections/ConsultationCta'
import FaqPreview from '@/sections/FaqPreview'
import Hero from '@/sections/Hero/Hero'
import { StepList } from '@/sections/ProcessSteps'
import type { PageProps, Section, ServicePageProps } from '@/types'

const base = (id: string, type: Section['type'], theme: Section['style']['theme'], extra: Partial<Section> = {}): Section => ({
  id, type, buttons: [], cards: [], slides: [],
  style: { theme, overlay: 55, padding: 'lg', align: 'start' },
  motion: { preset: 'fade-up', intensity: 100, delay: 0 },
  ...extra,
})

/**
 * Business solution detail (FR-031..033): hero, overview with what is included, the investor journey
 * (company formation), related FAQs, related solutions and a consultation call to action.
 * Sections reuse the CMS renderers so every page shares one visual language.
 */
export default function ServicePage(props: ServicePageProps) {
  const { site, t, locale } = useSite()
  const scope = useRef<HTMLDivElement>(null)
  useMotion(scope, `service-${props.service.slug}-${locale.code}`)
  const { service } = props
  const consultation = `/consultation?service=${service.slug}`
  const solutionsLabel = site.nav.find(n => n.href === '/services')?.label ?? t('allServices')

  // The shared section renderers read from a page-shaped object.
  const page = { ...props, services: props.related, regions: [], industries: {}, countries: [], testimonials: [], faqs: props.serviceFaqs, sections: [] } as unknown as PageProps

  const hero = base(`service-${service.slug}`, 'hero', 'dark', {
    eyebrow: props.serviceGroups[service.group], title: service.title, subtitle: service.tagline, media: service.media,
    buttons: [{ label: service.cta, href: consultation, style: 'gold' }, { label: t('allServices'), href: '/services', style: 'outline' }],
    style: { theme: 'dark', overlay: 58, padding: 'md', align: 'start' }, motion: { preset: 'zoom-in', intensity: 100, delay: 0 },
  })
  const faq = base('service-faq', 'faq', 'charcoal', { eyebrow: t('questions'), title: t('serviceQuestions') })
  const cta = base('service-cta', 'cta', 'dark', {
    title: t('ctaTitle'), body: t('ctaBody'),
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
      <Breadcrumbs trail={[{ label: solutionsLabel, href: '/services' }, { label: service.title }]} />

      <section aria-labelledby="overview-title" className="th-dark pad-lg">
        <div className="wrap grid gap-12 lg:grid-cols-[1.15fr_1fr] lg:gap-20">
          <div data-motion="fade-up">
            <p className="eyebrow mb-5 flex items-center gap-3"><span className="h-px w-8 bg-current" aria-hidden />{t('overview')}</p>
            <h2 id="overview-title" className="font-display t-display-l">{service.tagline}</h2>
            <div className="gold-rule my-8 w-32" data-motion="line" aria-hidden />
            <div className="space-y-4"><TextBlock text={service.overview} className="t-body-l muted measure" /></div>
            {service.note && <p className="accent mt-8 border-s-2 border-gold ps-5 text-lg font-medium measure">{service.note}</p>}
          </div>
          {service.scope.length > 0 && <aside aria-labelledby="scope-title" className="self-start rounded-[8px] border border-gold/30 bg-gradient-to-b from-white/[.04] to-transparent p-7 md:p-9" data-motion="fade-up">
            <h2 id="scope-title" className="eyebrow mb-6">{t('whatsIncluded')}</h2>
            <ul className="space-y-1">
              {service.scope.map(item => <li key={item} className="flex min-h-11 items-start gap-3 border-b border-white/[.06] py-3 last:border-0">
                <span className="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-gold/15 text-gold" aria-hidden><Check size={14} /></span>
                <span>{item}</span>
              </li>)}
            </ul>
          </aside>}
        </div>
      </section>

      {props.journey.length > 0 && <section aria-labelledby="journey-title" className="th-charcoal pad-lg">
        <div className="wrap">
          <p className="eyebrow mb-5">{t('howWeWork')}</p>
          <h2 id="journey-title" className="font-display t-display-l mb-14">{t('fromIdea')}</h2>
          <StepList steps={props.journey} motion={{ 'data-motion': 'stagger' }} />
        </div>
      </section>}

      {props.serviceFaqs.length > 0 && <FaqPreview section={faq} page={page} />}

      {props.related.length > 0 && <section aria-labelledby="related-title" className="th-dark pad-lg">
        <div className="wrap">
          <p className="eyebrow mb-5">{solutionsLabel}</p>
          <h2 id="related-title" className="font-display t-display-l mb-12">{t('relatedServices')}</h2>
          <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-motion="stagger">
            {props.related.map(item => <li key={item.slug}><ServiceCard service={item} media={props.media[item.media]} /></li>)}
          </ul>
        </div>
      </section>}

      <ConsultationCta section={cta} page={page} />
    </div>
  </SiteLayout>
}
