import '../css/app.css'
import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'
import type { ComponentType } from 'react'

const pages = import.meta.glob<{ default: ComponentType }>('./pages/**/*.tsx', { eager: true })

createInertiaApp({
  title: title => title || 'Creative Mark',
  resolve: name => pages[`./pages/${name}.tsx`].default,
  setup({ el, App, props }) {
    if (el) createRoot(el).render(<App {...props} />)
  },
  progress: { color: '#EBBD6C', delay: 200 },
})
