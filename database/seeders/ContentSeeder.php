<?php

namespace Database\Seeders;

use App\Content\PublishPage;
use App\Enums\ReviewStatus;
use App\Models\Country;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Region;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Seeds the presentation content ported from the React prototype (S-09) in Arabic and English.
 * Figures are unverified unless the owner confirmed them (RULE-01); testimonials carry no names (RULE-02).
 * English is the presentation's own text (approved). The Arabic text was drafted in the prototype and has not
 * been reviewed by a translator, so it is seeded as "review" (D-05). Other locales stay "missing" (RULE-06).
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $c = json_decode(file_get_contents(__DIR__.'/data/catalogue.json'), true, flags: JSON_THROW_ON_ERROR);

        $services = [];
        foreach ($c['services'] as $s) {
            $m = Service::updateOrCreate(['slug' => $s['slug']], ['icon' => $s['icon'], 'media' => $s['media'], 'sort_order' => $s['order'], 'status' => 'published',
                'needs_review' => (bool) preg_match('/\d/', $s['summary']['en'])]);
            $this->translate($m, ['title' => $s['title'], 'tagline' => $s['tagline'], 'summary' => $s['summary'], 'cta' => $s['cta']]);
            $services[$s['key']] = $m->id;
        }

        foreach ($c['regions'] as $i => $r) {
            $m = Region::updateOrCreate(['slug' => $r['slug']], ['media' => $r['media'], 'industries' => $r['industries'], 'sort_order' => $i + 1, 'status' => 'published', 'source' => $r['source']]);
            $this->translate($m, [
                'name' => $r['name'], 'capital' => $r['capital'], 'best_for' => $r['best_for'], 'description' => $r['description'],
                'opportunities' => ['en' => array_column($r['opportunities'], 'en'), 'ar' => array_column($r['opportunities'], 'ar')],
            ]);
            $m->services()->sync(array_map(fn ($k) => $services[$k], $r['services']));
        }

        foreach ($c['stats'] as $i => $s) {
            $m = Stat::updateOrCreate(['key' => $s['key']], ['value' => $s['value'], 'icon' => $s['icon'] ?? null, 'group' => $s['group'], 'verified' => $s['verified'], 'source' => $s['source'], 'sort_order' => $i + 1]);
            $this->translate($m, ['label' => $s['label']]);
        }

        foreach ($c['countries'] as $i => $s) {
            $m = Country::updateOrCreate(['code' => $s['code']], ['sort_order' => $i + 1, 'status' => 'published']);
            $this->translate($m, ['name' => $s['name']]);
        }

        Faq::query()->delete();
        foreach ($c['faqs'] as $f) {
            $m = Faq::create(['category' => $f['category'], 'sort_order' => $f['order'], 'status' => 'published', 'needs_review' => $f['needs_review'], 'show_on_home' => ! $f['needs_review']]);
            $this->translate($m, ['question' => $f['question'], 'answer' => $f['answer']]);
        }

        Testimonial::query()->delete();
        foreach ($c['testimonials'] as $i => $t) {
            $m = Testimonial::create(['verified' => false, 'consent_recorded_at' => null, 'sort_order' => $i + 1, 'status' => 'published']);
            $this->translate($m, ['quote' => $t['quote'], 'author' => $t['author'], 'context' => $t['context']]);
        }

        $this->settings($c);
        $this->home();
    }

    private function home(): void
    {
        $h = json_decode(file_get_contents(__DIR__.'/data/home.json'), true, flags: JSON_THROW_ON_ERROR);
        $page = Page::updateOrCreate(['key' => 'home'], ['draft_sections' => $h['sections']]);
        foreach ($h['translations'] as $locale => $t) {
            $page->translations()->updateOrCreate(['locale' => $locale], [...$t, 'slug' => null, 'review_status' => self::status($locale)]);
        }
        (new PublishPage)($page, note: 'Initial content from the presentation');
    }

    /** @param array<string, mixed> $c */
    private function settings(array $c): void
    {
        $set = fn (string $group, string $key, mixed $value) => Setting::updateOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);

        // Owner-supplied 2026-11-01 (client-contact-and-brand-inputs.md). Phone, email, WhatsApp and hours are still missing (G-01).
        $set('contact', 'offices', [
            ['key' => 'riyadh', 'country' => ['en' => 'Saudi Arabia', 'ar' => 'المملكة العربية السعودية'],
                'address' => ['en' => 'Riyadh, Al Olaya District, Al Bashari Street, Building 21', 'ar' => 'الرياض – حي العليا – شارع البشاري – مبنى رقم 21']],
            ['key' => 'cairo', 'country' => ['en' => 'Egypt', 'ar' => 'جمهورية مصر العربية'],
                'address' => ['en' => 'Cairo, Salah Salem, 24 Obour Buildings, in front of the October War Panorama', 'ar' => 'القاهرة – صلاح سالم – 24ج عمارات العبور – أمام بانوراما حرب أكتوبر']],
        ]);
        $set('contact', 'email', null);
        $set('contact', 'phone', null);
        $set('contact', 'whatsapp', null);
        $set('social', 'links', [
            'instagram' => 'https://www.instagram.com/creative_mark.co/',
            'facebook' => 'https://www.facebook.com/creativemark1',
            'linkedin' => 'https://www.linkedin.com/company/105348458/', // derived from the company id; confirm
        ]);
        // Owner decision D-07 (2026-11-01): no "translation under review" notice on the public site.
        $set('site', 'translation_notice', false);
        $set('catalogue', 'industries', $c['industries']);
        $set('catalogue', 'faq_categories', $c['faq_categories']);
        // RULE-04: prototype stock photos (Unsplash), placeholders until the client supplies or approves photography
        $set('media', 'placeholders', json_decode(file_get_contents(__DIR__.'/data/media.json'), true, flags: JSON_THROW_ON_ERROR));
        $set('navigation', 'header', [
            ['label' => ['en' => 'About', 'ar' => 'من نحن'], 'href' => '/about'],
            ['label' => ['en' => 'Business solutions', 'ar' => 'حلول الأعمال'], 'href' => '/services', 'mega' => 'services'],
            ['label' => ['en' => 'Our edge', 'ar' => 'ميزتنا'], 'href' => '/our-edge'],
            ['label' => ['en' => 'Regions', 'ar' => 'المناطق'], 'href' => '/regions'],
            ['label' => ['en' => 'Investor guide', 'ar' => 'دليل المستثمر'], 'href' => '/investor-guide'],
            ['label' => ['en' => 'News', 'ar' => 'الأخبار'], 'href' => '/news'],
            ['label' => ['en' => 'Case studies', 'ar' => 'دراسات الحالة'], 'href' => '/case-studies'],
        ]);
        $set('navigation', 'cta', ['label' => ['en' => 'Get consultation', 'ar' => 'احصل على استشارة'], 'href' => '/consultation']);
    }

    private static function status(string $locale): ReviewStatus
    {
        return $locale === 'en' ? ReviewStatus::Approved : ReviewStatus::Review;
    }

    /** @param array<string, array<string, mixed>|null> $fields field => [locale => value] */
    private function translate(Model $model, array $fields): void
    {
        foreach (['en', 'ar'] as $locale) {
            $row = array_map(fn ($v) => $v[$locale] ?? null, $fields);
            $model->translations()->updateOrCreate(['locale' => $locale], [...$row, 'review_status' => self::status($locale)]);
        }
    }
}
