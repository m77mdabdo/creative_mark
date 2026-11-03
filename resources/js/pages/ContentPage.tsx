import { useRef } from 'react'
import { Head } from '@inertiajs/react'
import Breadcrumbs from '@/components/Breadcrumbs'
import { useSite } from '@/hooks/useSite'
import SiteLayout from '@/layouts/SiteLayout'
import { useMotion } from '@/motion/useMotion'
import { SECTION_COMPONENTS } from '@/sections/registry'
import type { PageProps } from '@/types'

/** Every CMS page (home, about…) is a list of typed sections rendered by the registry. */
export default function ContentPage(page: PageProps & { preview?: boolean }) {
  const { locale, site, t } = useSite()
  const scope = useRef<HTMLDivElement>(null)
  useMotion(scope, `${page.pageKey}-${locale.code}`)
  const isHome = page.pageKey === 'home'

  return <SiteLayout showTranslationNotice={site.translationNotice && page.reviewStatus !== 'approved'}>
    <Head>
      <title>{page.seo.title}</title>
      <meta name="description" content={page.seo.description} />
      {page.preview && <meta name="robots" content="noindex" />}
    </Head>
    {/* Dashboard preview of an unpublished draft */}
    {page.preview && <div role="status" className="fixed inset-x-0 bottom-4 z-[60] mx-auto w-fit max-w-[calc(100%-2rem)] rounded-full bg-gold px-5 py-2.5 text-center text-sm font-semibold text-ink shadow-2xl">{t('previewBanner')}</div>}
    <div ref={scope}>
      {page.sections.map((section, i) => {
        const Component = SECTION_COMPONENTS[section.type]
        return <div key={section.id} className="contents">
          <Component section={section} page={page} />
          {!isHome && i === 0 && <Breadcrumbs trail={page.breadcrumbs ?? [{ label: page.title }]} />}
        </div>
      })}
    </div>
  </SiteLayout>
}
