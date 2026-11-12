<?php

namespace App\Content;

use App\Enums\ReviewStatus;
use App\Models\CaseStudy;
use App\Models\MediaAsset;
use App\Models\NewsArticle;
use App\Models\Page;
use App\Models\Region;
use App\Models\Service;
use App\Support\Locales;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The site's images: the photography shipped with the site plus images uploaded in the dashboard. Every image is
 * known by a key (App\Models\MediaAsset) with its folder, widths and alt text per language.
 * Uploads are re-encoded to WebP at several widths, which also strips metadata and anything that is not an image.
 */
final class MediaLibrary
{
    public const UPLOAD_WIDTHS = [800, 1600, 2400];

    /** Dashboard uploads land here first (the "local" disk), relative to storage/app/private. */
    public const UPLOAD_DIR = 'media-uploads';

    /** Images larger than this are refused before decoding, so a small file cannot expand to gigabytes in memory. */
    public const MAX_PIXELS = 40_000_000;

    /** @var array<string, array<string, mixed>>|null registry for this request */
    private static ?array $registry = null;

    /** @var array<string, string>|null */
    private static ?array $options = null;

    /**
     * Every image as key => {folder, widths, alt: {locale: text}, credit, uploaded_at}, read once per request.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::$registry ??= MediaAsset::query()->with('translations')->orderBy('id')->get()->mapWithKeys(fn (MediaAsset $m) => [$m->key => [
            'folder' => $m->folder, 'widths' => $m->widths, 'credit' => $m->credit,
            'alt' => $m->translations->pluck('alt', 'locale')->all(),
            'uploaded_at' => $m->isUpload() ? $m->created_at?->toIso8601String() : null,
        ]])->all();
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

    /**
     * The images a page needs, as the site renders them: a middle-sized src, every width in srcset and the alt text.
     *
     * @param  array<int, string|null>  $ids
     * @return array<string, array{src: string, srcset: string, alt: string}>
     */
    public static function responsive(array $ids, string $locale): array
    {
        $alts = self::all();
        $out = [];
        foreach (array_unique(array_filter($ids)) as $id) {
            if (! isset($alts[$id])) {
                continue;
            }
            // Each entry knows its folder and generated widths (hero photography has larger renditions).
            $base = '/media/'.($alts[$id]['folder'] ?? 'placeholders')."/$id";
            $widths = $alts[$id]['widths'] ?? [800, 1600];
            $out[$id] = [
                'src' => "$base-".$widths[intdiv(count($widths), 2)].'.webp',
                'srcset' => implode(', ', array_map(fn (int $w) => "$base-$w.webp {$w}w", $widths)),
                'alt' => $alts[$id]['alt'][$locale] ?? $alts[$id]['alt'][Locales::FALLBACK],
            ];
        }

        return $out;
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

    /** @param array<string, string|null> $alt alt text per language; an emptied language falls back to English */
    public static function updateAlt(string $key, array $alt): void
    {
        $asset = MediaAsset::query()->where('key', $key)->firstOrFail();
        DB::transaction(function () use ($asset, $alt) {
            foreach (Locales::codes() as $locale) {
                $text = is_string($alt[$locale] ?? null) ? trim(strip_tags($alt[$locale])) : '';
                if ($text === '') {
                    $locale === Locales::FALLBACK || $asset->translations()->where('locale', $locale)->delete();

                    continue;
                }
                $row = $asset->translations()->firstOrNew(['locale' => $locale]);
                $row->alt = mb_substr($text, 0, 255);
                if ($row->isDirty()) {
                    $row->review_status = ReviewStatus::Approved;
                    $row->reviewed_at = now();
                }
                $row->save();
            }
        });
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
        $asset = MediaAsset::query()->where('key', $key)->firstOrFail();
        foreach ($asset->widths ?? [] as $w) {
            @unlink(public_path("media/uploads/{$key}-{$w}.webp"));
        }
        $asset->delete();
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

    /**
     * Turns the path a dashboard upload field submitted into the absolute path of a file inside the upload folder,
     * or null. The value comes from the browser, so "..", absolute paths, other folders and symlinks out are refused.
     */
    public static function resolveUpload(mixed $file): ?string
    {
        if (! is_string($file) || $file === '' || str_contains($file, "\0") || str_contains($file, '\\')
            || str_starts_with($file, '/') || preg_match('~(^|/)\.\.?(/|$)~', $file)
            || ! str_starts_with($file, self::UPLOAD_DIR.'/') || substr_count($file, '/') !== 1) {
            return null;
        }
        $root = realpath(storage_path('app/private/'.self::UPLOAD_DIR));
        $path = realpath(storage_path('app/private/'.$file));
        if ($root === false || $path === false || ! is_file($path) || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $path;
    }

    /** Stores an image uploaded in the dashboard (see resolveUpload), then removes the temporary file. */
    public static function storeUpload(mixed $file, string $altEn, ?string $altAr = null): string
    {
        $path = self::resolveUpload($file);
        if ($path === null) {
            throw new RuntimeException('The uploaded file could not be found.');
        }
        try {
            return self::store($path, $altEn, $altAr);
        } finally {
            @unlink($path);
        }
    }

    /** Re-encodes an uploaded image and registers it. Returns its key. */
    public static function store(string $path, string $altEn, ?string $altAr = null): string
    {
        $size = @getimagesize($path);
        if ($size === false || $size[0] < 1 || $size[1] < 1) {
            throw new RuntimeException('The file is not a readable image.');
        }
        if ($size[0] * $size[1] > self::MAX_PIXELS) {
            throw new RuntimeException('The image has too many pixels.');
        }
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

        DB::transaction(function () use ($key, $widths, $altEn, $altAr) {
            $asset = MediaAsset::query()->create(['key' => $key, 'folder' => 'uploads', 'widths' => $widths, 'credit' => 'Creative Mark', 'uploaded_by' => auth()->id()]);
            foreach (array_filter(['en' => trim($altEn), 'ar' => $altAr ? trim($altAr) : null]) as $locale => $alt) {
                $asset->translations()->create(['locale' => $locale, 'alt' => mb_substr(strip_tags($alt), 0, 255), 'review_status' => ReviewStatus::Approved, 'reviewed_at' => now()]);
            }
        });
        self::flush();

        return $key;
    }
}
