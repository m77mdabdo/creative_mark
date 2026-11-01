/* Brand marks are not part of lucide; these are simplified single-path glyphs. */
const PATHS: Record<string, string> = {
  instagram: 'M12 7.3a4.7 4.7 0 1 0 0 9.4 4.7 4.7 0 0 0 0-9.4Zm0 7.7a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm6-7.9a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0ZM21.9 8.1c-.1-1.6-.4-3-1.6-4.2-1.2-1.2-2.6-1.5-4.2-1.6C14.5 2.2 9.5 2.2 7.9 2.3 6.3 2.4 4.9 2.7 3.7 3.9 2.5 5.1 2.2 6.5 2.1 8.1 2 9.7 2 14.3 2.1 15.9c.1 1.6.4 3 1.6 4.2 1.2 1.2 2.6 1.5 4.2 1.6 1.6.1 6.6.1 8.2 0 1.6-.1 3-.4 4.2-1.6 1.2-1.2 1.5-2.6 1.6-4.2.1-1.6.1-6.2 0-7.8Zm-2.1 9.7a3.2 3.2 0 0 1-1.8 1.8c-1.3.5-4.3.4-5.6.4s-4.4.1-5.6-.4a3.2 3.2 0 0 1-1.8-1.8c-.5-1.3-.4-4.3-.4-5.8s-.1-4.4.4-5.8a3.2 3.2 0 0 1 1.8-1.8c1.3-.5 4.3-.4 5.6-.4s4.4-.1 5.6.4a3.2 3.2 0 0 1 1.8 1.8c.5 1.3.4 4.3.4 5.8s.1 4.4-.4 5.8Z',
  facebook: 'M13.5 21.9v-7.4H16l.4-3h-2.9V9.6c0-.8.3-1.5 1.5-1.5h1.5V5.5c-.3 0-1.2-.1-2.2-.1-2.2 0-3.7 1.3-3.7 3.8v2.3H8.1v3h2.5v7.4h2.9Z',
  linkedin: 'M6.9 21H3.3V9.2h3.6V21ZM5.1 7.6a2.1 2.1 0 1 1 0-4.2 2.1 2.1 0 0 1 0 4.2ZM21 21h-3.6v-5.7c0-1.4 0-3.1-1.9-3.1s-2.2 1.5-2.2 3V21H9.7V9.2h3.4v1.6h.1c.5-.9 1.6-1.9 3.4-1.9 3.6 0 4.3 2.4 4.3 5.5V21Z',
}

export const SOCIAL_LABELS: Record<string, string> = { instagram: 'Instagram', facebook: 'Facebook', linkedin: 'LinkedIn' }

export default function SocialIcon({ name }: { name: string }) {
  const d = PATHS[name]
  if (!d) return null
  return <svg width={16} height={16} viewBox="0 0 24 24" fill="currentColor" aria-hidden><path d={d} /></svg>
}
