import { Sparkles } from 'lucide-react'
import { CMS_ICONS } from './cmsIcons'

type Props = { name?: string | null; size?: number; stroke?: number; className?: string }

/** An icon chosen in the CMS by name (list in scripts/build-cms-icons.mjs); unknown names fall back to Sparkles. */
export default function Icon({ name, size = 20, stroke = 1.5, className = '' }: Props) {
  const Component = (name && CMS_ICONS[name]) || Sparkles
  return <Component size={size} strokeWidth={stroke} className={className} aria-hidden />
}
