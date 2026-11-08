import { Head, usePage } from '@inertiajs/react'
import { useSite } from '@/hooks/useSite'

/** Staff looking at unpublished changes (preview mode): a reminder on every page and a way out. */
export default function PreviewBar() {
  const { preview, t } = useSite()
  const { url } = usePage()
  if (!preview) return null

  return <>
    <Head><meta name="robots" content="noindex" /></Head>
    <div role="status" className="fixed inset-x-0 bottom-4 z-[60] mx-auto flex w-fit max-w-[calc(100%-2rem)] flex-wrap items-center justify-center gap-x-4 gap-y-1 rounded-full bg-gold px-5 py-2.5 text-center text-sm font-semibold text-ink shadow-2xl">
      <span>{t('previewBanner')}</span>
      <a href={`/preview/exit?path=${encodeURIComponent(url.split('?')[0])}`} className="underline underline-offset-4 hover:no-underline">{t('exitPreview')}</a>
    </div>
  </>
}
