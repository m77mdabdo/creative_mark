/** Client wordmark (raster from the supplied .ai file; replace with the vector logo when it arrives). */
export default function Logo({ className = '' }: { className?: string }) {
  return <img src="/brand/wordmark-gold.png" alt="Creative Mark" width={805} height={368} className={`w-auto ${className}`} />
}
