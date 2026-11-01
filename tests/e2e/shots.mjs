// Visual QA captures: node tests/e2e/shots.mjs [baseUrl] [outDir]
import { chromium } from '@playwright/test'
const base = process.argv[2] ?? 'http://127.0.0.1:8000'
const out = process.argv[3] ?? 'storage/qa'
const browser = await chromium.launch()
const errors = []
for (const locale of ['ar', 'en']) {
  for (const [name, viewport] of [['390', { width: 390, height: 844 }], ['1440', { width: 1440, height: 900 }], ['360', { width: 360, height: 640 }]]) {
    const page = await browser.newPage({ viewport, reducedMotion: 'reduce' })
    page.on('console', m => m.type() === 'error' && errors.push(`${locale}-${name}: ${m.text()}`))
    page.on('pageerror', e => errors.push(`${locale}-${name}: ${e.message}`))
    await page.goto(`${base}/${locale}`, { waitUntil: 'networkidle' })
    await page.screenshot({ path: `${out}/home-${locale}-${name}-fold.png` })
    if (name !== '360') {
      await page.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += 600) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 60)) } window.scrollTo(0, 0) })
      await page.waitForTimeout(400)
      await page.screenshot({ path: `${out}/home-${locale}-${name}-full.png`, fullPage: true })
    }
    const m = await page.evaluate(() => {
      const cta = document.querySelector('#hero a.btn')?.getBoundingClientRect()
      return { overflowX: document.documentElement.scrollWidth - window.innerWidth, h1: document.querySelectorAll('h1').length, ctaBottom: cta ? Math.round(cta.bottom) : null, vh: window.innerHeight }
    })
    console.log(locale, name, JSON.stringify(m))
    await page.close()
  }
}
console.log('console errors:', errors.length ? errors : 'none')
await browser.close()
