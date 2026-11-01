// Admin captures with the local QA account from .env: node tests/e2e/admin-shots.mjs
import { chromium } from '@playwright/test'
import { readFileSync } from 'node:fs'
const env = Object.fromEntries(readFileSync('.env', 'utf8').split('\n').filter(l => l.includes('=')).map(l => [l.slice(0, l.indexOf('=')), l.slice(l.indexOf('=') + 1)]))
const base = 'http://127.0.0.1:8000'
const browser = await chromium.launch()
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } })
const errors = []
page.on('pageerror', e => errors.push(e.message))
await page.goto(`${base}/admin/login`)
await page.fill('input[type=email]', env.QA_ADMIN_EMAIL)
await page.fill('input[type=password]', env.QA_ADMIN_PASSWORD)
await page.click('button[type=submit]')
await page.waitForURL(`${base}/admin`)
for (const [name, path] of [['dashboard', '/admin'], ['services', '/admin/services'], ['faqs', '/admin/faqs'], ['regions', '/admin/regions']]) {
  await page.goto(base + path, { waitUntil: 'networkidle' })
  await page.screenshot({ path: `storage/qa/admin-${name}.png` })
}
await page.goto(`${base}/admin/faqs`, { waitUntil: 'networkidle' })
await page.locator('table tbody tr a').first().click().catch(() => {})
await page.waitForLoadState('networkidle')
await page.screenshot({ path: 'storage/qa/admin-faq-edit.png', fullPage: true })
console.log('errors:', errors.length ? errors : 'none')
await browser.close()
