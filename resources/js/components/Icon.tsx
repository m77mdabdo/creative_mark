import { Award, BadgeCheck, Briefcase, Building2, CalendarCheck, Calculator, Compass, Factory, FileCheck2, FileText, Globe2, Landmark, LineChart, Map, MapPin, MapPinned, MonitorCheck, Plane, Receipt, Shapes, Sparkles, Target, TrendingUp, Users, Wallet, Workflow, type LucideIcon } from 'lucide-react'

/**
 * Icons the CMS may reference by name. Keeping an explicit list keeps the bundle small;
 * add an import and an entry here to make a new icon available to editors.
 */
const ICONS: Record<string, LucideIcon> = {
  Award, BadgeCheck, Briefcase, Building2, CalendarCheck, Calculator, Compass, Factory, FileCheck2, FileText, Globe2,
  Landmark, LineChart, Map, MapPin, MapPinned, MonitorCheck, Plane, Receipt, Shapes, Target, TrendingUp, Users, Wallet, Workflow,
}

type Props = { name?: string | null; size?: number; stroke?: number; className?: string }

export default function Icon({ name, size = 20, stroke = 1.5, className = '' }: Props) {
  const Component = (name && ICONS[name]) || Sparkles
  return <Component size={size} strokeWidth={stroke} className={className} aria-hidden />
}
