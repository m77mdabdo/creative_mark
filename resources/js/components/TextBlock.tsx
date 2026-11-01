/** Renders CMS text as paragraphs split on blank lines. CMS text is never interpreted as HTML. */
export default function TextBlock({ text, className = '' }: { text?: string | null; className?: string }) {
  if (!text) return null
  return <>{text.split(/\n{2,}/).map((paragraph, i) => <p key={i} className={className}>{paragraph}</p>)}</>
}
