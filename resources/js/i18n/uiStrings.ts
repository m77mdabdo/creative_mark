/**
 * Interface strings (labels, controls, notices), as opposed to CMS content.
 * They move to the ui_strings table with the translation workspace (FR-181); until then add keys here.
 */
const STRINGS = {
  skip: { en: 'Skip to content', ar: 'تخطَّ إلى المحتوى' },
  menu: { en: 'Menu', ar: 'القائمة' },
  close: { en: 'Close', ar: 'إغلاق' },
  language: { en: 'Language', ar: 'اللغة' },
  home: { en: 'Creative Mark, home', ar: 'كرييتف مارك، الرئيسية' },
  mainNav: { en: 'Main', ar: 'الرئيسية' },
  allServices: { en: 'All business solutions', ar: 'جميع حلول الأعمال' },
  startCompany: { en: 'Start your company', ar: 'ابدأ شركتك' },
  startCompanyText: { en: 'Company formation, licensing and setup in one coordinated journey.', ar: 'التأسيس والتراخيص والتجهيز في رحلة واحدة منسقة.' },
  pending: { en: 'This page is being translated. Some text may appear in English.', ar: 'هذه الترجمة قيد المراجعة، وقد تظهر بعض النصوص بالإنجليزية.' },
  pendingAr: { en: 'Arabic text awaiting review by a translator.', ar: 'النص العربي بانتظار مراجعة مترجم.' },
  offices: { en: 'Our offices', ar: 'مكاتبنا' },
  company: { en: 'Company', ar: 'الشركة' },
  solutions: { en: 'Business solutions', ar: 'حلول الأعمال' },
  follow: { en: 'Follow us', ar: 'تابعنا' },
  rights: { en: 'All rights reserved.', ar: 'جميع الحقوق محفوظة.' },
  tagline: { en: 'One place, endless solutions', ar: 'مكان واحد، حلول لا تنتهي' },
  scroll: { en: 'Scroll', ar: 'مرّر' },
  capital: { en: 'Capital', ar: 'العاصمة' },
  bestFor: { en: 'Best for', ar: 'الأنسب لـ' },
  opportunities: { en: 'Opportunities', ar: 'الفرص' },
  recommended: { en: 'Recommended solutions', ar: 'حلول موصى بها' },
  filterBySector: { en: 'Filter by sector', ar: 'تصفية حسب القطاع' },
  all: { en: 'All', ar: 'الكل' },
  regionList: { en: 'All 13 regions', ar: 'المناطق الثلاث عشرة' },
  mapLabel: { en: 'Map of Saudi Arabia’s 13 regions. Choose a region to see what it is best for.', ar: 'خريطة مناطق المملكة الثلاث عشرة. اختر منطقة لمعرفة ما تتميز به.' },
  mapCredit: { en: 'Boundaries © OpenStreetMap contributors (geoBoundaries), ODbL 1.0', ar: 'الحدود © مساهمو OpenStreetMap (geoBoundaries)، ترخيص ODbL 1.0' },
  selected: { en: 'Selected region', ar: 'المنطقة المختارة' },
  clientReview: { en: 'Client review', ar: 'رأي عميل' },
  unverified: { en: 'Name withheld until the client approves publication.', ar: 'الاسم محجوب حتى يوافق العميل على النشر.' },
  prev: { en: 'Previous', ar: 'السابق' },
  next: { en: 'Next', ar: 'التالي' },
  pause: { en: 'Pause slideshow', ar: 'إيقاف العرض مؤقتاً' },
  play: { en: 'Play slideshow', ar: 'تشغيل العرض' },
  slide: { en: 'Slide', ar: 'شريحة' },
  of: { en: 'of', ar: 'من' },
  goToSlide: { en: 'Show slide', ar: 'عرض الشريحة' },
  photoNote: { en: 'Illustrative photography', ar: 'صور توضيحية' },
} satisfies Record<string, { en: string; ar: string }>

export type UiKey = keyof typeof STRINGS
export const ui = (key: UiKey, locale: string): string => (STRINGS[key] as Record<string, string>)[locale] ?? STRINGS[key].en
