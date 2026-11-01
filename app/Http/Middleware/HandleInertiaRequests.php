<?php

namespace App\Http\Middleware;

use App\Models\Service;
use App\Models\Setting;
use App\Support\Locales;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();
        $pick = fn (?array $m) => $m === null ? null : ($m[$locale] ?? $m[Locales::FALLBACK]);
        $nav = Setting::group('navigation');
        $contact = Setting::group('contact');

        return [
            ...parent::share($request),
            'locale' => [
                'code' => $locale,
                'tag' => Locales::tag($locale),
                'dir' => Locales::dir($locale),
                'available' => collect(Locales::ALL)->map(fn ($l, $code) => ['code' => $code, 'tag' => $l['tag'], 'native' => $l['native'], 'dir' => $l['dir']])->values(),
            ],
            'site' => fn () => [
                'nav' => array_map(fn ($n) => [...$n, 'label' => $pick($n['label'])], $nav['header'] ?? []),
                'cta' => isset($nav['cta']) ? [...$nav['cta'], 'label' => $pick($nav['cta']['label'])] : null,
                'megaServices' => Service::query()->where('status', 'published')->orderBy('sort_order')->withLocale($locale)->get()
                    ->map(fn (Service $s) => ['slug' => $s->slug, 'icon' => $s->icon, 'title' => $s->t('title', $locale), 'tagline' => $s->t('tagline', $locale)])->all(),
                'offices' => array_map(fn ($o) => ['key' => $o['key'], 'country' => $pick($o['country']), 'address' => $pick($o['address'])], $contact['offices'] ?? []),
                'email' => $contact['email'] ?? null,
                'phone' => $contact['phone'] ?? null,
                'whatsapp' => $contact['whatsapp'] ?? null,
                'social' => Setting::group('social')['links'] ?? [],
            ],
        ];
    }
}
