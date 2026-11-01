/**
 * Interface labels come from lang/ui/{locale}.json through the shared `ui` prop (English fills any gap).
 * Add a key to lang/ui/en.json and lang/ui/ar.json; `php artisan cm:translate` fills the other languages.
 */
export type UiKey = keyof typeof import('../../../lang/ui/en.json')
export type UiStrings = Record<UiKey, string>
