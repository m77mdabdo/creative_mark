// Responsive check across device widths: node tests/e2e/responsive.mjs /about ar,en,ur
import { chromium } from '@playwright/test'
const path = process.argv[2] ?? ''
const locales = (process.argv[3] ?? 'ar,en').split(',')
const widths = [320, 360, 375, 390, 414, 430, 768, 834, 1024, 1280, 1440, 1920]
const browser = await chromium.launch()
const problems = []
for (const locale of locales) {
  for (const width of widths) {
    const page = await browser.newPage({ viewport: { width, height: width < 768 ? 844 : 900 }, reducedMotion: 'reduce' })
    const errors = []
    page.on('pageerror', e => errors.push(e.message))
    await page.goto(`${process.env.BASE ?? "http://127.0.0.1:8000"}/${locale}${path}`, { waitUntil: 'networkidle' })
    const m = await page.evaluate(() => {
      const wide = [...document.querySelectorAll('main *')].filter(el => el.getBoundingClientRect().right > window.innerWidth + 1 && getComputedStyle(el).position !== 'fixed' && !el.closest('.swipe'))
      const small = [...document.querySelectorAll('main a, main button')].filter(el => { const r = el.getBoundingClientRect(); return r.width > 0 && (r.height < 24 || r.width < 24) })
      return { overflow: document.documentElement.scrollWidth - window.innerWidth, wide: wide.slice(0, 3).map(e => e.tagName + '.' + e.className.toString().slice(0, 40)), small: small.length, h1: document.querySelectorAll('h1').length }
    })
    if (m.overflow > 0 || m.h1 !== 1 || errors.length) problems.push({ locale, width, ...m, errors })
    if ([320, 768, 1440].includes(width)) await page.screenshot({ path: `storage/qa/resp-${locale}${path.replace(/\//g, '-')}-${width}.png`, fullPage: true })
    await page.close()
  }
}
console.log(problems.length ? JSON.stringify(problems, null, 1) : `OK: ${locales.length} locales × ${widths.length} widths, no horizontal overflow, one h1, no errors`)
await browser.close()
