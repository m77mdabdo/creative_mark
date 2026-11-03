<?php

namespace App\Content;

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
