<?php

use App\Content\PublishPage;
use App\Enums\ReviewStatus;
use App\Models\Page;
use App\Models\Setting;
use Database\Seeders\ContentSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Owner request 2026-11-24: the "Our portfolio" page (سابقة الأعمال) and its entry in the top menu, after Our edge.
 * The page starts from database/seeders/data/portfolio.json. A site that already has the page or the menu entry
 * keeps what it has. A new, empty database gets both from the seeder instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Page::query()->exists()) {
            return;
        }
        if (! Page::query()->where('key', 'portfolio')->exists()) {
            $data = json_decode(file_get_contents(database_path('seeders/data/portfolio.json')), true, flags: JSON_THROW_ON_ERROR);
            $page = Page::create(['key' => 'portfolio', 'draft_sections' => $data['sections']]);
            foreach ($data['translations'] as $locale => $t) {
                $page->translations()->create([...$t, 'locale' => $locale, 'slug' => 'portfolio',
                    'review_status' => $locale === 'en' ? ReviewStatus::Approved : ReviewStatus::Review]);
            }
            (new PublishPage)($page, note: 'Our portfolio page added');
        }

        $header = Setting::query()->where('group', 'navigation')->where('key', 'header')->first();
        if ($header && ! collect($header->value)->contains('href', '/portfolio')) {
            $items = array_values($header->value);
            $after = collect($items)->search(fn ($item) => ($item['href'] ?? null) === '/our-edge');
            array_splice($items, $after === false ? count($items) : $after + 1, 0, [ContentSeeder::PORTFOLIO_NAV]);
            $header->update(['value' => $items]);
        }
    }

    public function down(): void
    {
        $header = Setting::query()->where('group', 'navigation')->where('key', 'header')->first();
        $header?->update(['value' => array_values(array_filter($header->value, fn ($item) => ($item['href'] ?? null) !== '/portfolio'))]);
        Page::query()->where('key', 'portfolio')->first()?->delete();
    }
};
