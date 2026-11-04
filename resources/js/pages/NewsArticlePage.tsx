import { useRef, useState } from 'react'
import { Head } from '@inertiajs/react'
import { ArrowUpRight, Check, Clock, Link2, Newspaper } from 'lucide-react'
import Breadcrumbs from '@/components/Breadcrumbs'
import Media from '@/components/Media'
import NewsCard from '@/components/NewsCard'
import SmartLink from '@/components/SmartLink'
import TextBlock from '@/components/TextBlock'
import { useSite } from '@/hooks/useSite'
import SiteLayout from '@/layouts/SiteLayout'
import { useMotion } from '@/motion/useMotion'
import ConsultationCta from '@/sections/ConsultationCta'
import { categoryKey, formatNewsDate, readingMinutes } from '@/sections/news/format'
import type { NewsArticlePageProps, PageProps, Section } from '@/types'

/**
 * A newsroom story: cinematic photo header, the team's summary, what it means for investors,
 * key figures, the original report, related stories and a consultation call to action.
 */
export default function NewsArticlePage(props: NewsArticlePageProps & { preview?: boolean }) {
  const { t, locale } = useSite()
  const scope = useRef<HTMLDivElement>(null)
  useMotion(scope, `article-${props.article.slug}-${locale.code}`)
  const { article } = props
  const [copied, setCopied] = useState(false)
  const page = { ...props, services: [], regions: [], countries: [], testimonials: [], faqs: [], sections: [], industries: {} } as unknown as PageProps

  const copy = async () => {
    try {
      await navigator.clipboard.writeText(window.location.href)
      setCopied(true)
      window.setTimeout(() => setCopied(false), 2500)
    } catch { /* clipboard blocked: nothing to do */ }
  }

  const cta: Section = {
    id: 'article-cta', type: 'cta', cards: [], slides: [],
    title: t('ctaTitle'), body: t('ctaBody'),
    buttons: [{ label: t('bookConsultation'), href: '/consultation', style: 'gold' }],
    style: { theme: 'dark', overlay: 74, padding: 'lg', align: 'center', bgMedia: 'news-corniche' },
    motion: { preset: 'reveal-mask', intensity: 100, delay: 0 },
  }

  return <SiteLayout>
    <Head>
      <title>{props.seo.title}</title>
      <meta name="description" content={props.seo.description} />
      <meta property="og:type" content="article" />
      {props.preview && <meta name="robots" content="noindex" />}
    </Head>
    {props.preview && <div role="status" className="fixed inset-x-0 bottom-4 z-[60] mx-auto w-fit max-w-[calc(100%-2rem)] rounded-full bg-gold px-5 py-2.5 text-center text-sm font-semibold text-ink shadow-2xl">{t('previewBanner')}</div>}
    <div ref={scope}>
      <header className="th-dark relative flex min-h-[78svh] items-end overflow-hidden">
        <Media media={props.media[article.media]} eager sizes="100vw" className="absolute inset-0 h-full w-full animate-[cm-kenburns_14s_ease-out_both] motion-reduce:animate-none" />
        <div className="absolute inset-0 bg-gradient-to-t from-ink via-black/60 to-black/20" aria-hidden />
        <div className="wrap relative pb-12 pt-36 md:pb-16">
          <p className="mb-6 flex flex-wrap items-center gap-3 text-xs">
            <span className="rounded-full bg-gold px-3 py-1 font-semibold text-ink">{t(categoryKey(article.category))}</span>
            <span className="text-white/75"><time dateTime={article.date}>{formatNewsDate(article.date, locale.tag)}</time></span>
            <span className="flex items-center gap-1.5 text-white/75"><Clock size={13} aria-hidden />{readingMinutes(article.body)} {t('minRead')}</span>
          </p>
          <h1 className="font-display t-display-l max-w-4xl text-white">{article.title}</h1>
          <p className="t-body-l mt-6 max-w-3xl text-white/80">{article.excerpt}</p>
        </div>
      </header>
      <Breadcrumbs trail={[{ label: props.newsTitle, href: '/news' }, { label: article.title }]} />

      <section className="th-dark pad-lg" aria-label={article.title}>
        <div className="wrap grid gap-12 grid-cols-1 lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-20">
          <article className="min-w-0 [overflow-wrap:anywhere]">
            <div className="news-body space-y-6 text-lg leading-relaxed text-white/85" data-motion="fade-up">
              <TextBlock text={article.body} className="measure" />
            </div>
            {article.takeaway && <aside className="mt-12 rounded-[var(--cm-radius)] border border-gold/40 bg-gradient-to-br from-gold/[.12] to-transparent p-6 md:p-8" data-motion="fade-up">
              <p className="eyebrow mb-3">{t('whatItMeans')}</p>
              <p className="font-display text-2xl leading-snug md:text-3xl">{article.takeaway}</p>
            </aside>}
            <p className="mt-10 flex items-start gap-2 border-t border-white/10 pt-6 text-sm text-white/50"><Newspaper size={16} className="mt-0.5 shrink-0" aria-hidden />{t('summaryBy')}</p>
          </article>

          <aside className="space-y-6 lg:sticky lg:top-28 lg:self-start">
            {article.facts.length > 0 && <dl className="overflow-hidden rounded-[var(--cm-radius)] border border-white/10">
              {article.facts.map(f => <div key={f.label} className="flex flex-col-reverse gap-1 border-b border-white/10 p-5 last:border-0">
                <dt className="text-sm text-white/60">{f.label}</dt>
                <dd className="font-display text-4xl text-gold">{f.value}</dd>
              </div>)}
            </dl>}
            <div className="rounded-[var(--cm-radius)] border border-white/10 p-5">
              <p className="mb-1 text-xs text-white/50">{t('source')}</p>
              <p className="mb-1 text-lg">{article.source.name}</p>
              <p className="mb-4 text-xs text-white/50">{t('reportDate')}: <time dateTime={article.date}>{formatNewsDate(article.date, locale.tag)}</time></p>
              <a href={article.source.url} target="_blank" rel="noopener noreferrer" className="btn btn-gold w-full">
                {t('readOriginal')}<ArrowUpRight size={15} className="flip-rtl" aria-hidden /><span className="sr-only"> ({t('opensNewTab')})</span>
              </a>
              <button type="button" onClick={copy} className="btn btn-outline mt-3 w-full" aria-live="polite">
                {copied ? <><Check size={15} aria-hidden />{t('linkCopied')}</> : <><Link2 size={15} aria-hidden />{t('copyLink')}</>}
              </button>
            </div>
            {props.latest.length > 0 && <nav aria-label={t('latest')} className="hidden lg:block">
              <p className="eyebrow mb-3">{t('latest')}</p>
              <ul className="divide-y divide-white/10 border-y border-white/10">
                {props.latest.slice(0, 4).map(l => <li key={l.slug}><SmartLink href={`/news/${l.slug}`} className="block py-3 text-sm leading-snug text-white/75 hover:text-gold">{l.title}</SmartLink></li>)}
              </ul>
            </nav>}
          </aside>
        </div>
      </section>

      {props.related.length > 0 && <section aria-labelledby="related-title" className="th-charcoal pad-lg">
        <div className="wrap">
          <div className="mb-10 flex flex-wrap items-end justify-between gap-4">
            <h2 id="related-title" className="font-display t-display-l">{t('relatedNews')}</h2>
            <SmartLink href="/news" className="inline-flex min-h-11 items-center gap-1.5 text-sm text-gold hover:underline">{t('allNews')}<ArrowUpRight size={15} className="flip-rtl" aria-hidden /></SmartLink>
          </div>
          <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-motion="stagger">
            {props.related.map(item => <li key={item.slug}><NewsCard item={item} media={props.media[item.media]} /></li>)}
          </ul>
        </div>
      </section>}

      <ConsultationCta section={cta} page={page} />
    </div>
  </SiteLayout>
}
