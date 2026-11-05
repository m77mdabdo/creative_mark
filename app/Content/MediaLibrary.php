<?php

namespace App\Content;

use App\Models\CaseStudy;
use App\Models\NewsArticle;
use App\Models\Page;
use App\Models\Region;
use App\Models\Service;
use App\Models\Setting;
use App\Support\Locales;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The site's images: the photography shipped with the site plus images uploaded in the dashboard. Every image is
 * known by a key; the registry (setting media.placeholders) holds its folder, widths and alt text per language.
 * Uploads are re-encoded to WebP at several widths, which also strips metadata and anything that is not an image.
 */
final class MediaLibrary
{
    public const UPLOAD_WIDTHS = [800, 1600, 2400];

    /** @var array<string, array<string, mixed>>|null registry for this request */
    private static ?array $registry = null;

    /** @var array<string, string>|null */
    private static ?array $options = null;

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return self::$registry ??= Setting::group('media')['placeholders'] ?? [];
    }

    public static function url(string $key, ?int $width = null): ?string
    {
        $item = self::all()[$key] ?? null;
        if (! $item) {
            return null;
        }
        $widths = $item['widths'] ?? [800, 1600];
        $width ??= $widths[0];

        return '/media/'.($item['folder'] ?? 'placeholders')."/{$key}-{$width}.webp";
    }

    /** Forget this request's copy (after another process changed the registry). */
    public static function flush(): void
    {
        self::$registry = null;
        self::$options = null;
    }

    /**
     * Where each image is used, for the library screen and to protect it from deletion.
     *
     * @return array<string, list<string>> key => places
     */
    public static function usage(): array
    {
        $used = [];
        $add = function (?string $key, string $place) use (&$used) {
            if ($key && ! in_array($place, $used[$key] ?? [], true)) {
                $used[$key][] = $place;
            }
        };
        $walk = function ($value, string $place) use (&$walk, $add) {
            foreach ((array) $value as $k => $v) {
                if (is_array($v)) {
                    $walk($v, $place);
                } elseif (in_array($k, ['media', 'media2', 'bgMedia'], true) && is_string($v)) {
                    $add($v, $place);
                }
            }
        };
        $locale = app()->getLocale();
        foreach (Page::query()->with(['publishedRevision', 'translations'])->get() as $page) {
            $place = __('Page').': '.($page->t('title', $locale) ?? $page->key);
            $walk($page->draft_sections ?? [], $place);
            $walk($page->publishedRevision?->sections ?? [], $place);
        }
        foreach ([[Service::class, 'Service', 'title'], [Region::class, 'Region', 'name'], [NewsArticle::class, 'News article', 'title'], [CaseStudy::class, 'Case study', 'title']] as [$model, $label, $field]) {
            foreach ($model::query()->with('translations')->get() as $record) {
                $add($record->getAttributes()['media'] ?? null, __($label).': '.$record->t($field, $locale));
            }
        }

        return $used;
    }

    /** @param array<string, string|null> $alt alt text per language */
    public static function updateAlt(string $key, array $alt): void
    {
        $registry = self::all();
        abort_unless(isset($registry[$key]), 404);
        $registry[$key]['alt'] = array_filter(array_map(fn ($t) => is_string($t) ? trim(strip_tags($t)) : null, $alt), 'filled');
        Setting::updateOrCreate(['group' => 'media', 'key' => 'placeholders'], ['value' => $registry]);
        self::flush();
    }

    /** Only uploaded images that nothing uses can be deleted; the photography shipped with the site stays. */
    public static function canDelete(string $key, ?array $usage = null): bool
    {
        return (self::all()[$key]['folder'] ?? null) === 'uploads' && empty(($usage ?? self::usage())[$key]);
    }

    public static function delete(string $key): void
    {
        abort_unless(self::canDelete($key), 403);
        $item = self::all()[$key];
        foreach ($item['widths'] ?? [] as $w) {
            @unlink(public_path("media/uploads/{$key}-{$w}.webp"));
        }
        $registry = self::all();
        unset($registry[$key]);
        Setting::updateOrCreate(['group' => 'media', 'key' => 'placeholders'], ['value' => $registry]);
        self::flush();
    }

    public static function alt(string $key): string
    {
        $alt = self::all()[$key]['alt'] ?? [];

        return $alt[app()->getLocale()] ?? $alt[Locales::FALLBACK] ?? $key;
    }

    /** Select options: a thumbnail and the alt text, newest uploads first. @return array<string, string> */
    public static function options(): array
    {
        return self::$options ??= collect(self::all())->sortByDesc(fn ($m) => $m['uploaded_at'] ?? '')
            ->mapWithKeys(fn ($m, $key) => [$key => '<span style="display:flex;align-items:center;gap:.6rem"><img src="'.e(self::url($key)).'" alt="" loading="lazy" style="width:3.5rem;height:2.2rem;object-fit:cover;border-radius:.35rem;flex-shrink:0">'.e(self::alt($key)).'</span>'])
            ->all();
    }

    public static function preview(?string $key): ?HtmlString
    {
        return $key && ($url = self::url($key))
            ? new HtmlString('<img src="'.e($url).'" alt="" style="margin-top:.5rem;width:100%;max-width:22rem;aspect-ratio:16/9;object-fit:cover;border-radius:.6rem">')
            : null;
    }

    /** Re-encodes an uploaded image and registers it. Returns its key. */
    public static function store(string $path, string $altEn, ?string $altAr = null): string
    {
        $data = @file_get_contents($path);
        $image = $data !== false ? @imagecreatefromstring($data) : false;
        if (! $image) {
            throw new RuntimeException('The file is not a readable image.');
        }
        $key = 'up-'.now()->format('ymd').'-'.Str::lower(Str::random(6));
        $dir = public_path('media/uploads');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $sourceWidth = imagesx($image);
        $widths = array_values(array_filter(self::UPLOAD_WIDTHS, fn ($w) => $w <= max($sourceWidth, self::UPLOAD_WIDTHS[0])));
        foreach ($widths as $w) {
            $scaled = imagescale($image, min($w, $sourceWidth));
            imagewebp($scaled, "{$dir}/{$key}-{$w}.webp", 82);
            imagedestroy($scaled);
        }
        imagedestroy($image);

        $registry = self::all();
        $registry[$key] = [
            'alt' => array_filter(['en' => trim($altEn), 'ar' => $altAr ? trim($altAr) : null]),
            'folder' => 'uploads', 'widths' => $widths, 'credit' => 'Creative Mark', 'uploaded_at' => now()->toIso8601String(),
        ];
        Setting::updateOrCreate(['group' => 'media', 'key' => 'placeholders'], ['value' => $registry]);
        self::$registry = $registry;
        self::$options = null;

        return $key;
    }
}
