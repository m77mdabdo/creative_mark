import { Award, BadgeCheck, Briefcase, Building2, CalendarCheck, Calculator, Compass, FileCheck2, Globe2, Landmark, LineChart, Map, MapPinned, Plane, Shapes, Sparkles, Target, TrendingUp, Users, Workflow, type LucideIcon } from 'lucide-react'

/**
 * Icons the CMS may reference by name. Keeping an explicit list keeps the bundle small;
 * add an import and an entry here to make a new icon available to editors.
 */
const ICONS: Record<string, LucideIcon> = {
  Award, BadgeCheck, Briefcase, Building2, CalendarCheck, Calculator, Compass, FileCheck2, Globe2,
  Landmark, LineChart, Map, MapPinned, Plane, Shapes, Target, TrendingUp, Users, Workflow,
}

type Props = { name?: string | null; size?: number; stroke?: number; className?: string }

export default function Icon({ name, size = 20, stroke = 1.5, className = '' }: Props) {
  const Component = (name && ICONS[name]) || Sparkles
  return <Component size={size} strokeWidth={stroke} className={className} aria-hidden />
}
