import { useState } from 'react'
import { Head } from '@inertiajs/react'
import { Check, Copy } from 'lucide-react'
import Media from '@/components/Media'
import SmartLink from '@/components/SmartLink'
import { useSite } from '@/hooks/useSite'
import SiteLayout from '@/layouts/SiteLayout'
import type { MediaItem } from '@/types'

/** Confirmation after booking: the reference number and what happens next. */
export default function ConsultationThanks({ reference, media }: { reference: string; media: Record<string, MediaItem> }) {
  const { t } = useSite()
  const [copied, setCopied] = useState(false)
  const copy = async () => {
    try { await navigator.clipboard.writeText(reference); setCopied(true); window.setTimeout(() => setCopied(false), 2500) } catch { /* clipboard blocked */ }
  }

  return <SiteLayout>
    <Head><title>{`${t('thanksTitle')} | Creative Mark`}</title><meta name="robots" content="noindex" /></Head>
    <section className="th-dark relative flex min-h-[100svh] items-center overflow-hidden">
      <Media media={media['guide-boulevard']} eager sizes="100vw" className="absolute inset-0 h-full w-full opacity-40" />
      <div className="absolute inset-0 bg-gradient-to-b from-black/80 via-black/70 to-black" aria-hidden />
      <div className="wrap relative py-32 text-center">
        <div className="mx-auto mb-8 grid h-20 w-20 place-items-center rounded-full bg-gold text-ink shadow-[0_0_0_12px_rgba(235,189,108,.12)] animate-[cm-rise_.8s_var(--ease-out)_both] motion-reduce:animate-none" aria-hidden><Check size={36} strokeWidth={2.5} /></div>
        <h1 className="font-display t-display-l mx-auto max-w-3xl text-white">{t('thanksTitle')}</h1>
        <p className="t-body-l mx-auto mt-5 max-w-xl text-white/75">{t('thanksBody')}</p>

        <div className="mx-auto mt-10 inline-flex flex-col items-center gap-3 rounded-[var(--cm-radius)] border border-gold/40 bg-black/60 px-8 py-6 backdrop-blur-md">
          <p className="text-xs uppercase tracking-[.2em] text-white/55">{t('yourReference')}</p>
          <p dir="ltr" className="font-mono text-3xl tracking-[.18em] text-gold">{reference}</p>
          <button type="button" onClick={copy} className="inline-flex min-h-11 items-center gap-2 text-sm text-white/70 hover:text-gold" aria-live="polite">
            {copied ? <><Check size={14} aria-hidden />{t('copied')}</> : <><Copy size={14} aria-hidden />{t('copy')}</>}
          </button>
        </div>

        <ol className="mx-auto mt-12 grid max-w-3xl gap-3 text-start sm:grid-cols-3">
          {(['next1', 'next2', 'next3'] as const).map((k, i) => <li key={k} className="rounded-[var(--cm-radius)] border border-white/10 bg-white/[.03] p-5">
            <span className="mb-2 block font-display text-2xl text-gold">{String(i + 1).padStart(2, '0')}</span>
            <span className="text-sm text-white/80">{t(k)}</span>
          </li>)}
        </ol>
        <p className="mt-6 text-xs text-white/45">{t('checkSpam')}</p>

        <div className="mt-10 flex flex-wrap justify-center gap-3">
          <SmartLink href="/" className="btn btn-gold">{t('backHome')}</SmartLink>
          <SmartLink href="/investor-guide" className="btn btn-outline">{t('readGuide')}</SmartLink>
        </div>
      </div>
    </section>
  </SiteLayout>
}
