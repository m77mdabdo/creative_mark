// Captures each home section separately: node tests/e2e/segments.mjs <locale> <width>
import { chromium } from '@playwright/test'
const [locale = 'en', width = '1440'] = process.argv.slice(2)
const browser = await chromium.launch()
const page = await browser.newPage({ viewport: { width: +width, height: +width > 800 ? 900 : 844 }, reducedMotion: 'reduce' })
await page.goto(`http://127.0.0.1:8000/${locale}`, { waitUntil: 'networkidle' })
for (const id of await page.$$eval('main section', s => s.map(x => x.id))) {
  const el = page.locator(`#${id}`)
  await el.scrollIntoViewIfNeeded(); await page.waitForTimeout(250)
  await el.screenshot({ path: `storage/qa/seg-${locale}-${width}-${id}.png` })
}
await page.locator('footer').screenshot({ path: `storage/qa/seg-${locale}-${width}-footer.png` })
await browser.close()
