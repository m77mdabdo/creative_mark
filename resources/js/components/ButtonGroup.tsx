import { ArrowRight } from 'lucide-react'
import type { Button } from '@/types'
import SmartLink from './SmartLink'

export default function ButtonGroup({ buttons, className = '' }: { buttons: Button[]; className?: string }) {
  if (!buttons.length) return null

  return <div className={`flex flex-wrap items-center gap-3 ${className}`}>
    {buttons.map(button => (
      <SmartLink key={`${button.href}-${button.label}`} href={button.href} className={`btn btn-${button.style}`}>
        {button.label}
        <ArrowRight size={15} className="btn-arrow flip-rtl" aria-hidden />
      </SmartLink>
    ))}
  </div>
}
