<?php

namespace App\Http\Middleware;

use App\Drafts\PreviewMode;
use App\Models\Service;
use App\Models\Setting;
use App\Support\Locales;
use App\Translation\UiStrings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public const ANNOUNCEMENT_SPEEDS = ['slow', 'normal', 'fast'];

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        // Closures: shared props resolve after the route's SetLocale middleware has run.
        return [
            ...parent::share($request),
            'locale' => fn () => [
                'code' => app()->getLocale(),
                'tag' => Locales::tag(app()->getLocale()),
                'dir' => Locales::dir(app()->getLocale()),
                'available' => collect(Locales::ALL)->map(fn ($l, $code) => ['code' => $code, 'tag' => $l['tag'], 'native' => $l['native'], 'dir' => $l['dir']])->values(),
            ],
            'site' => fn () => $this->site(app()->getLocale()),
            'ui' => fn () => UiStrings::forLocale(app()->getLocale()),
            // Staff looking at unpublished changes (App\Drafts\PreviewMode)
            'preview' => fn () => app(PreviewMode::class)->active($request),
        ];
    }

    /** @return array<string, mixed> */
    private function site(string $locale): array
    {
        $pick = fn (?array $m) => $m === null ? null : ($m[$locale] ?? $m[Locales::FALLBACK]);
        $nav = Setting::group('navigation');
        $contact = Setting::group('contact');

        return [
            'nav' => array_map(fn ($n) => [...$n, 'label' => $pick($n['label'])], $nav['header'] ?? []),
            'cta' => isset($nav['cta']) ? [...$nav['cta'], 'label' => $pick($nav['cta']['label'])] : null,
            'megaServices' => Service::query()->live()->orderBy('sort_order')->withLocale($locale)->get()
                ->map(fn (Service $s) => ['slug' => $s->slug, 'icon' => $s->icon, 'title' => $s->t('title', $locale), 'tagline' => $s->t('tagline', $locale)])->all(),
            'offices' => array_map(fn ($o) => ['key' => $o['key'], 'country' => $pick($o['country']), 'address' => $pick($o['address'])], $contact['offices'] ?? []),
            'email' => $contact['email'] ?? null,
            'phone' => $contact['phone'] ?? null,
            'whatsapp' => $contact['whatsapp'] ?? null,
            'social' => Setting::group('social')['links'] ?? [],
            // RULE-06 notice; switched off by the owner on 2026-11-01 (docs/decisions.md, D-07)
            'translationNotice' => (bool) (Setting::group('site')['translation_notice'] ?? false),
            'announcement' => $this->announcement($pick),
        ];
    }

    /**
     * The scrolling bar above the menu (Settings › Announcement bar): null when it is switched off or has no
     * message in this language or English.
     *
     * @return array{label: ?string, speed: string, items: list<array{text: string, href: ?string}>}|null
     */
    private function announcement(\Closure $pick): ?array
    {
        $bar = Setting::group('announcement');
        if (empty($bar['enabled'])) {
            return null;
        }
        $items = [];
        foreach ($bar['items'] ?? [] as $item) {
            $text = $pick($item['text'] ?? []);
            if (filled($text)) {
                $items[] = ['text' => $text, 'href' => filled($item['href'] ?? null) ? $item['href'] : null];
            }
        }

        return $items === [] ? null : [
            'label' => $pick($bar['label'] ?? null),
            'speed' => in_array($bar['speed'] ?? null, self::ANNOUNCEMENT_SPEEDS, true) ? $bar['speed'] : 'normal',
            'items' => $items,
        ];
    }
}
