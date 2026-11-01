import { useRef } from 'react'
import { Head as InertiaHead } from '@inertiajs/react'
import SiteLayout from '@/layouts/SiteLayout'
import { SECTIONS } from '@/sections'
import { useMotion } from '@/motion/useMotion'
import { useSite } from '@/lib/useSite'
import type { HomeProps } from '@/types'

export default function Home(props: HomeProps) {
  const { locale } = useSite()
  const scope = useRef<HTMLDivElement>(null)
  useMotion(scope, `home-${locale.code}`)
  return <SiteLayout notice={props.reviewStatus === 'approved' ? null : props.reviewStatus}>
    <InertiaHead>
      <title>{props.seo.title}</title>
      <meta name="description" content={props.seo.description} />
    </InertiaHead>
    <div ref={scope}>
      {props.sections.map(s => {
        const C = SECTIONS[s.type]
        return C ? <C key={s.id} s={s} data={props} /> : null
      })}
    </div>
  </SiteLayout>
}
