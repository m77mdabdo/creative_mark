import { useRef } from 'react'
import { Head } from '@inertiajs/react'
import { useSite } from '@/hooks/useSite'
import SiteLayout from '@/layouts/SiteLayout'
import { useMotion } from '@/motion/useMotion'
import { SECTION_COMPONENTS } from '@/sections/registry'
import type { HomeProps } from '@/types'

export default function Home(page: HomeProps) {
  const { locale, site } = useSite()
  const scope = useRef<HTMLDivElement>(null)
  useMotion(scope, `home-${locale.code}`)

  return <SiteLayout showTranslationNotice={site.translationNotice && page.reviewStatus !== 'approved'}>
    <Head>
      <title>{page.seo.title}</title>
      <meta name="description" content={page.seo.description} />
    </Head>
    <div ref={scope}>
      {page.sections.map(section => {
        const Component = SECTION_COMPONENTS[section.type]
        return <Component key={section.id} section={section} page={page} />
      })}
    </div>
  </SiteLayout>
}
