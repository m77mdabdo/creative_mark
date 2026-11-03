import type { ReactNode } from 'react'
import { useSite } from '@/hooks/useSite'
import { useSmoothScroll } from '@/motion/useSmoothScroll'
import WhatsAppButton from '@/components/WhatsAppButton'
import Footer from './site/Footer'
import Header from './site/Header'
import TranslationNotice from './site/TranslationNotice'

type Props = { children: ReactNode; showTranslationNotice?: boolean; hideWhatsApp?: boolean }

export default function SiteLayout({ children, showTranslationNotice = false, hideWhatsApp = false }: Props) {
  const { t } = useSite()
  useSmoothScroll()

  return <>
    <a href="#main" className="skip-link">{t('skip')}</a>
    <Header />
    {showTranslationNotice && <TranslationNotice />}
    <main id="main" tabIndex={-1} className="outline-none">{children}</main>
    <Footer />
    <WhatsAppButton hidden={hideWhatsApp} />
  </>
}
