import { useId, useState } from 'react'
import { Minus, Plus } from 'lucide-react'
import ButtonGroup from '@/components/ButtonGroup'
import { groupMotion } from './shared/motionAttributes'
import SectionHeading from './shared/SectionHeading'
import SectionShell from './shared/SectionShell'
import type { SectionProps } from './shared/types'

/** FAQ preview (FR-018): accessible accordion, one answer open at a time, items needing review never sent. */
export default function FaqPreview(props: SectionProps) {
  const { section, page } = props
  const [openId, setOpenId] = useState<number | null>(page.faqs[0]?.id ?? null)
  const uid = useId()

  return <SectionShell {...props}>
    <div className="wrap grid gap-14 lg:grid-cols-[1fr_1.6fr]">
      <div>
        <SectionHeading section={section} />
        <ButtonGroup buttons={section.buttons} className="mt-8" />
      </div>

      <div className="space-y-3" {...groupMotion(section)}>
        {page.faqs.map(faq => {
          const open = openId === faq.id
          const questionId = `${uid}-q${faq.id}`
          const answerId = `${uid}-a${faq.id}`
          return <div key={faq.id} className={`rounded-[6px] border transition-colors ${open ? 'border-gold/60 bg-white/[.03]' : 'border-white/12 hover:border-gold/40'}`}>
            <h3>
              <button type="button" id={questionId} aria-expanded={open} aria-controls={answerId} onClick={() => setOpenId(open ? null : faq.id)}
                className="flex min-h-16 w-full items-center justify-between gap-6 px-6 py-5 text-start text-lg font-medium">
                <span>{faq.question}</span>
                <span className={`grid h-9 w-9 shrink-0 place-items-center rounded-full border transition ${open ? 'border-gold bg-gold text-ink' : 'border-white/25'}`} aria-hidden>
                  {open ? <Minus size={14} /> : <Plus size={14} />}
                </span>
              </button>
            </h3>
            <div id={answerId} role="region" aria-labelledby={questionId} hidden={!open} className="px-6 pb-6">
              <p className="muted measure leading-relaxed">{faq.answer}</p>
            </div>
          </div>
        })}
      </div>
    </div>
  </SectionShell>
}
