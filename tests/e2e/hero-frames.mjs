// Captures the hero slideshow over time: node tests/e2e/hero-frames.mjs [locale] [width]
import { chromium } from '@playwright/test'
const [locale = 'en', width = '1440'] = process.argv.slice(2)
const browser = await chromium.launch()
const page = await browser.newPage({ viewport: { width: +width, height: +width > 800 ? 900 : 844 } })
const errors = []
page.on('pageerror', e => errors.push(e.message))
page.on('console', m => m.type() === 'error' && errors.push(m.text()))
await page.goto(`http://127.0.0.1:8000/${locale}`, { waitUntil: 'networkidle' })
let last = 0
for (const t of [1.5, 5, 8.3, 9.5, 17.5]) {
  await page.waitForTimeout((t - last) * 1000); last = t
  await page.screenshot({ path: `storage/qa/hero-${locale}-${width}-t${t}.png` })
}
console.log('slide label:', await page.locator('#hero [aria-current="true"]').getAttribute('aria-label'))
console.log('errors:', errors.length ? errors : 'none')
await browser.close()
