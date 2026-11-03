import { useMemo, useRef, useState, type FormEvent, type ReactNode } from 'react'
import { Head, useForm } from '@inertiajs/react'
import { ArrowLeft, ArrowRight, Building2, Check, Clock, MapPin, Phone, ShieldCheck, Video } from 'lucide-react'
import Flag from '@/components/Flag'
import Icon from '@/components/Icon'
import Media from '@/components/Media'
import { useSite } from '@/hooks/useSite'
import type { UiKey } from '@/i18n/uiStrings'
import SiteLayout from '@/layouts/SiteLayout'
import type { MediaItem } from '@/types'

type Props = {
  title: string
  seo: { title: string; description: string }
  services: { slug: string; title: string; icon: string }[]
  regions: { slug: string; name: string }[]
  countries: { code: string; name: string }[]
  dates: string[]
  slots: string[]
  taken: Record<string, string[]>
  modes: string[]
  stages: string[]
  prefill: { service: string | null; region: string | null }
  formToken: string
  media: Record<string, MediaItem>
}

type Form = {
  service: string; region: string; stage: string
  name: string; email: string; phone: string; company: string; country: string
  meeting_mode: string; preferred_date: string; preferred_slot: string
  message: string; consent: boolean; form_token: string; website: string
}

const STEPS = ['stepNeeds', 'stepAbout', 'stepTime', 'stepReview'] as const
const FIELDS_BY_STEP: (keyof Form)[][] = [
  ['service', 'region', 'stage'],
  ['name', 'email', 'phone', 'company', 'country'],
  ['meeting_mode', 'preferred_date', 'preferred_slot'],
  ['message', 'consent', 'form_token', 'website'],
]
const MODE_ICONS: Record<string, ReactNode> = { video: <Video size={18} />, phone: <Phone size={18} />, office_riyadh: <Building2 size={18} />, office_cairo: <Building2 size={18} /> }

/** Server error codes and Laravel rule failures mapped to friendly, translated messages. */
function errorMessage(field: string, raw: string, t: (k: UiKey) => string): string {
  if (raw === 'slot_taken') return t('errSlotTaken')
  if (raw === 'too_many') return t('errTooMany')
  if (raw === 'expired') return t('errExpired')
  if (raw === 'no_html') return t('errNoHtml')
  if (field === 'consent') return t('errConsent')
  if (field === 'email') return t('errEmail')
  if (field === 'phone') return t('errPhone')
  if (/required/i.test(raw)) return t('errRequired')
  return t('errGeneric')
}

/**
 * Consultation booking in four steps: needs, contact details, date and time (Riyadh), review and consent.
 * Each step checks its own fields before moving on; the server validates everything again.
 * Taken slots come from the server and cannot be chosen.
 */
export default function ConsultationPage(props: Props) {
  const { t, locale, site, href } = useSite()
  const [step, setStep] = useState(0)
  const [local, setLocal] = useState<Partial<Record<keyof Form, string>>>({})
  const top = useRef<HTMLDivElement>(null)
  const form = useForm<Form>({
    service: props.prefill.service ?? '', region: props.prefill.region ?? '', stage: '',
    name: '', email: '', phone: '', company: '', country: '',
    meeting_mode: 'video', preferred_date: '', preferred_slot: '',
    message: '', consent: false, form_token: props.formToken, website: '',
  })
  const d = form.data

  const dateFmt = useMemo(() => ({
    weekday: new Intl.DateTimeFormat(locale.tag, { weekday: 'short', timeZone: 'UTC' }),
    day: new Intl.DateTimeFormat(locale.tag, { day: 'numeric', numberingSystem: 'latn', timeZone: 'UTC' }),
    month: new Intl.DateTimeFormat(locale.tag, { month: 'short', timeZone: 'UTC' }),
    full: new Intl.DateTimeFormat(locale.tag, { weekday: 'long', day: 'numeric', month: 'long', numberingSystem: 'latn', calendar: 'gregory', timeZone: 'UTC' }),
  }), [locale.tag])
  const asDate = (iso: string) => new Date(`${iso}T00:00:00Z`)

  const err = (field: keyof Form) => local[field] ?? (form.errors[field] ? errorMessage(field, form.errors[field] as string, t) : undefined)
  const generalError = (form.errors as Record<string, string>).form

  /** Client-side check of one step (the server repeats every rule). */
  const check = (s: number) => {
    const e: Partial<Record<keyof Form, string>> = {}
    if (s === 0 && !d.stage) e.stage = t('errRequired')
    if (s === 1) {
      if (d.name.trim().length < 2) e.name = t('errRequired')
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(d.email.trim())) e.email = t('errEmail')
      if (!/^\+?[0-9][0-9\s\-()]{6,20}$/.test(d.phone.trim())) e.phone = t('errPhone')
      if (!d.country) e.country = t('errRequired')
    }
    if (s === 2) {
      if (!d.meeting_mode) e.meeting_mode = t('errRequired')
      if (!d.preferred_date) e.preferred_date = t('errRequired')
      if (!d.preferred_slot) e.preferred_slot = t('errRequired')
    }
    if (s === 3 && !d.consent) e.consent = t('errConsent')
    setLocal(e)
    return Object.keys(e).length === 0
  }

  const go = (s: number) => {
    setStep(s)
    top.current?.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' })
  }
  const next = () => { if (check(step)) go(step + 1) }

  const submit = (e: FormEvent) => {
    e.preventDefault()
    if (!check(3)) return
    form.post(href('/consultation'), {
      preserveScroll: true,
      onError: errors => {
        const first = FIELDS_BY_STEP.findIndex(fields => fields.some(f => f in errors))
        if (first >= 0 && first !== step) go(first)
        if ('preferred_slot' in errors) form.setData('preferred_slot', '')
      },
    })
  }

  const set = <K extends keyof Form>(key: K, value: Form[K]) => {
    form.setData(key as never, value as never)
    setLocal(l => ({ ...l, [key]: undefined }))
    form.clearErrors(key)
  }

  const takenOn = (date: string) => props.taken[date] ?? []
  const service = props.services.find(s => s.slug === d.service)
  const region = props.regions.find(r => r.slug === d.region)
  const country = props.countries.find(c => c.code === d.country)

  const label = 'mb-3 block text-sm font-medium text-white/85'
  const input = 'block w-full rounded-[var(--cm-radius)] border border-white/15 bg-black/40 px-4 py-3.5 text-white placeholder:text-white/30 transition focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold aria-[invalid=true]:border-red-400'
  const chip = (on: boolean) => `flex min-h-12 items-center gap-3 rounded-[var(--cm-radius)] border px-4 py-3 text-start text-sm transition ${on ? 'border-gold bg-gold/10 text-white' : 'border-white/12 bg-white/[.02] text-white/75 hover:border-white/30'}`
  const Error = ({ field }: { field: keyof Form }) => err(field) ? <p id={`${field}-error`} role="alert" className="mt-2 text-sm text-red-300">{err(field)}</p> : null
  const a11y = (field: keyof Form) => ({ 'aria-invalid': Boolean(err(field)), 'aria-describedby': err(field) ? `${field}-error` : undefined })

  return <SiteLayout hideWhatsApp>
    <Head>
      <title>{props.seo.title}</title>
      <meta name="description" content={props.seo.description} />
      <meta name="robots" content="noindex" />
    </Head>
    <div className="th-dark grid min-h-screen lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
      {/* Story panel */}
      <aside className="relative overflow-hidden lg:sticky lg:top-0 lg:h-screen">
        <Media media={props.media['guide-towers']} eager sizes="(min-width: 1024px) 42vw, 100vw" className="absolute inset-0 h-full w-full" />
        <div className="absolute inset-0 bg-gradient-to-t from-black via-black/75 to-black/40" aria-hidden />
        <div className="relative flex h-full flex-col justify-end px-5 pb-10 pt-32 md:px-10 lg:px-14 lg:pb-14">
          <p className="eyebrow mb-4 flex items-center gap-3"><span className="h-px w-8 bg-current" aria-hidden />Creative Mark</p>
          <h1 className="font-display t-display-l text-white">{props.title}</h1>
          <p className="t-body-l mt-5 max-w-md text-white/75">{t('bookIntro')}</p>
          <ul className="mt-8 hidden space-y-3 text-sm text-white/80 md:block">
            {(['bookPoint1', 'bookPoint2', 'bookPoint3'] as const).map(k => <li key={k} className="flex gap-3">
              <span className="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-gold/20 text-gold" aria-hidden><Check size={12} /></span>{t(k)}
            </li>)}
          </ul>
          <ul className="mt-8 hidden gap-6 border-t border-white/15 pt-6 text-xs text-white/60 lg:flex">
            {site.offices.map(o => <li key={o.key} className="flex gap-2"><MapPin size={14} className="mt-0.5 shrink-0 text-gold" aria-hidden /><span><span className="block text-white/85">{o.country}</span>{o.address}</span></li>)}
          </ul>
        </div>
      </aside>

      {/* Form */}
      <div ref={top} className="scroll-mt-20 px-5 pb-20 pt-10 md:px-10 lg:px-16 lg:pt-36">
        <ol className="mb-10 grid grid-cols-4 gap-2" aria-label={t('stepReview')}>
          {STEPS.map((s, i) => <li key={s}>
            <button type="button" disabled={i > step} onClick={() => i < step && go(i)} aria-current={i === step ? 'step' : undefined}
              className="group w-full text-start disabled:cursor-default">
              <span className={`mb-2 block h-1 rounded-full transition ${i <= step ? 'bg-gold' : 'bg-white/15'}`} />
              <span className={`hidden text-xs sm:block ${i === step ? 'text-white' : 'text-white/50'}`}>{String(i + 1).padStart(2, '0')} · {t(s)}</span>
            </button>
          </li>)}
        </ol>
        <p className="mb-6 text-xs text-white/50 sm:hidden">{t('step')} {step + 1} {t('of')} 4 · {t(STEPS[step])}</p>

        {generalError && <p role="alert" className="mb-6 rounded-[var(--cm-radius)] border border-red-400/40 bg-red-500/10 p-4 text-sm text-red-200">{errorMessage('form', generalError, t)}</p>}

        <form onSubmit={submit} noValidate className="max-w-2xl">
          {/* Honeypot: hidden from people and assistive technology */}
          <div aria-hidden="true" className="absolute -start-[9999px] h-px w-px overflow-hidden">
            <label>Website<input type="text" name="website" tabIndex={-1} autoComplete="off" value={d.website} onChange={e => form.setData('website', e.target.value)} /></label>
          </div>

          {step === 0 && <div className="space-y-10">
            <fieldset>
              <legend className={label}>{t('qService')}</legend>
              <div className="grid gap-2 sm:grid-cols-2">
                {[...props.services.map(s => ({ value: s.slug, label: s.title, icon: s.icon })), { value: '', label: t('notSure'), icon: 'Compass' }].map(o =>
                  <label key={o.value || 'none'} className={`cursor-pointer ${chip(d.service === o.value)}`}>
                    <input type="radio" name="service" className="sr-only" checked={d.service === o.value} onChange={() => set('service', o.value)} />
                    <Icon name={o.icon} size={18} className="shrink-0 text-gold" />{o.label}
                  </label>)}
              </div>
            </fieldset>
            <div>
              <label htmlFor="region" className={label}>{t('qRegion')}</label>
              <select id="region" className={input} value={d.region} onChange={e => set('region', e.target.value)}>
                <option value="">{t('notSure')}</option>
                {props.regions.map(r => <option key={r.slug} value={r.slug}>{r.name}</option>)}
              </select>
            </div>
            <fieldset {...a11y('stage')}>
              <legend className={label}>{t('qStage')} <span className="text-gold" aria-hidden>*</span></legend>
              <div className="grid gap-2 sm:grid-cols-3">
                {props.stages.map(s => <label key={s} className={`cursor-pointer ${chip(d.stage === s)}`}>
                  <input type="radio" name="stage" className="sr-only" checked={d.stage === s} onChange={() => set('stage', s)} />{t(`stage_${s}` as UiKey)}
                </label>)}
              </div>
              <Error field="stage" />
            </fieldset>
          </div>}

          {step === 1 && <div className="grid gap-6 sm:grid-cols-2">
            <div className="sm:col-span-2">
              <label htmlFor="name" className={label}>{t('fName')} <span className="text-gold" aria-hidden>*</span></label>
              <input id="name" className={input} autoComplete="name" maxLength={120} value={d.name} onChange={e => set('name', e.target.value)} {...a11y('name')} />
              <Error field="name" />
            </div>
            <div>
              <label htmlFor="email" className={label}>{t('fEmail')} <span className="text-gold" aria-hidden>*</span></label>
              <input id="email" type="email" dir="ltr" className={input} autoComplete="email" maxLength={190} value={d.email} onChange={e => set('email', e.target.value)} {...a11y('email')} />
              <Error field="email" />
            </div>
            <div>
              <label htmlFor="phone" className={label}>{t('fPhone')} <span className="text-gold" aria-hidden>*</span></label>
              <input id="phone" type="tel" dir="ltr" className={input} autoComplete="tel" inputMode="tel" placeholder="+966 5X XXX XXXX" maxLength={24} value={d.phone} onChange={e => set('phone', e.target.value)} {...a11y('phone')} />
              <Error field="phone" />
            </div>
            <div>
              <label htmlFor="company" className={label}>{t('fCompany')}</label>
              <input id="company" className={input} autoComplete="organization" maxLength={160} value={d.company} onChange={e => set('company', e.target.value)} {...a11y('company')} />
              <Error field="company" />
            </div>
            <div>
              <label htmlFor="country" className={label}>{t('fCountry')} <span className="text-gold" aria-hidden>*</span></label>
              <select id="country" className={input} value={d.country} onChange={e => set('country', e.target.value)} {...a11y('country')}>
                <option value="" disabled>—</option>
                {props.countries.map(c => <option key={c.code} value={c.code}>{c.name}</option>)}
                <option value="xx">{t('otherCountry')}</option>
              </select>
              <Error field="country" />
            </div>
          </div>}

          {step === 2 && <div className="space-y-10">
            <fieldset {...a11y('meeting_mode')}>
              <legend className={label}>{t('qMode')}</legend>
              <div className="grid gap-2 sm:grid-cols-2">
                {props.modes.map(m => <label key={m} className={`cursor-pointer ${chip(d.meeting_mode === m)}`}>
                  <input type="radio" name="meeting_mode" className="sr-only" checked={d.meeting_mode === m} onChange={() => set('meeting_mode', m)} />
                  <span className="text-gold" aria-hidden>{MODE_ICONS[m]}</span>{t(`mode_${m}` as UiKey)}
                </label>)}
              </div>
            </fieldset>
            <fieldset {...a11y('preferred_date')}>
              <legend className={label}>{t('qDate')} <span className="text-gold" aria-hidden>*</span></legend>
              <div className="-mx-5 flex gap-2 overflow-x-auto px-5 pb-2 md:mx-0 md:grid md:grid-cols-5 md:overflow-visible md:px-0">
                {props.dates.map(date => {
                  const full = takenOn(date).length >= props.slots.length
                  const on = d.preferred_date === date
                  return <label key={date} className={`w-[76px] shrink-0 cursor-pointer rounded-[var(--cm-radius)] border p-3 text-center transition md:w-auto ${on ? 'border-gold bg-gold text-ink' : full ? 'cursor-not-allowed border-white/5 text-white/25' : 'border-white/12 hover:border-white/35'}`}>
                    <input type="radio" name="preferred_date" className="sr-only" disabled={full} checked={on}
                      onChange={() => { set('preferred_date', date); if (takenOn(date).includes(d.preferred_slot)) form.setData('preferred_slot', '') }} />
                    <span className="block text-[11px] uppercase">{dateFmt.weekday.format(asDate(date))}</span>
                    <span className="font-display block text-2xl leading-tight">{dateFmt.day.format(asDate(date))}</span>
                    <span className="block text-[11px]">{dateFmt.month.format(asDate(date))}</span>
                  </label>
                })}
              </div>
              <Error field="preferred_date" />
            </fieldset>
            <fieldset {...a11y('preferred_slot')} disabled={!d.preferred_date}>
              <legend className={label}>{t('qSlot')} <span className="text-gold" aria-hidden>*</span></legend>
              <div className="grid grid-cols-3 gap-2 sm:grid-cols-5">
                {props.slots.map(slot => {
                  const taken = takenOn(d.preferred_date).includes(slot)
                  const on = d.preferred_slot === slot
                  return <label key={slot} className={`relative flex min-h-12 cursor-pointer flex-col items-center justify-center rounded-[var(--cm-radius)] border text-sm tabular-nums transition ${on ? 'border-gold bg-gold font-semibold text-ink' : taken || !d.preferred_date ? 'cursor-not-allowed border-white/5 text-white/25' : 'border-white/12 hover:border-white/35'}`}>
                    <input type="radio" name="preferred_slot" className="sr-only" disabled={taken || !d.preferred_date} checked={on} onChange={() => set('preferred_slot', slot)} />
                    <span dir="ltr">{slot}</span>{taken && <span className="text-[10px]">{t('slotTaken')}</span>}
                  </label>
                })}
              </div>
              <p className="mt-3 flex items-center gap-2 text-xs text-white/50"><Clock size={13} aria-hidden />{t('riyadhNote')}</p>
              <Error field="preferred_slot" />
            </fieldset>
          </div>}

          {step === 3 && <div className="space-y-8">
            <div className="rounded-[var(--cm-radius)] border border-gold/30 bg-gradient-to-b from-white/[.04] to-transparent">
              {[
                [t('stepNeeds'), [service?.title ?? t('notSure'), region?.name, t(`stage_${d.stage}` as UiKey)].filter(Boolean).join(' · '), 0],
                [t('stepAbout'), <span key="a" className="flex flex-wrap items-center gap-x-2">{d.name} · <span dir="ltr">{d.email}</span> · <span dir="ltr">{d.phone}</span>{country && <> · <Flag code={country.code} size={16} />{country.name}</>}{d.company && <> · {d.company}</>}</span>, 1],
                [t('stepTime'), d.preferred_date ? `${dateFmt.full.format(asDate(d.preferred_date))} · ${d.preferred_slot} · ${t(`mode_${d.meeting_mode}` as UiKey)}` : '', 2],
              ].map(([title, value, s]) => <div key={s as number} className="flex items-start justify-between gap-4 border-b border-white/10 p-5 last:border-0">
                <div className="min-w-0"><p className="mb-1 text-xs text-gold">{title}</p><div className="text-sm text-white/85">{value}</div></div>
                <button type="button" onClick={() => go(s as number)} className="shrink-0 text-xs text-white/60 underline-offset-4 hover:text-gold hover:underline">{t('edit')}</button>
              </div>)}
            </div>
            <div>
              <label htmlFor="message" className={label}>{t('fMessage')}</label>
              <textarea id="message" rows={5} maxLength={2000} className={input} value={d.message} onChange={e => set('message', e.target.value)} {...a11y('message')} />
              <Error field="message" />
            </div>
            <div>
              <label className="flex cursor-pointer items-start gap-3 text-sm text-white/80">
                <input type="checkbox" className="mt-1 h-5 w-5 shrink-0 accent-[var(--cm-gold)]" checked={d.consent} onChange={e => set('consent', e.target.checked)} {...a11y('consent')} />
                <span className="flex gap-2"><ShieldCheck size={16} className="mt-0.5 shrink-0 text-gold" aria-hidden />{t('consentText')}</span>
              </label>
              <Error field="consent" />
            </div>
          </div>}

          <div className="mt-12 flex items-center justify-between gap-4 border-t border-white/10 pt-6">
            {step > 0 ? <button type="button" onClick={() => go(step - 1)} className="btn btn-outline"><ArrowLeft size={16} className="flip-rtl" aria-hidden />{t('back')}</button> : <span />}
            {step < 3
              ? <button type="button" onClick={next} className="btn btn-gold">{t('continue')}<ArrowRight size={16} className="flip-rtl" aria-hidden /></button>
              : <button type="submit" disabled={form.processing} className="btn btn-gold disabled:opacity-60">{form.processing ? t('sending') : t('confirmBooking')}<Check size={16} aria-hidden /></button>}
          </div>
        </form>
      </div>
    </div>
  </SiteLayout>
}
