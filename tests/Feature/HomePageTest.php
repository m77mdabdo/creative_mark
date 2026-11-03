<?php

namespace Tests\Feature;

use App\Content\PublishPage;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Stat;
use App\Models\Testimonial;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ContentSeeder::class);
    }

    public function test_root_redirects_to_arabic_by_default(): void
    {
        $this->get('/', ['Accept-Language' => ''])->assertStatus(302)->assertRedirect('/ar');
    }

    public function test_root_follows_accept_language(): void
    {
        $this->get('/', ['Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8'])->assertRedirect('/fr');
        $this->get('/', ['Accept-Language' => 'de-DE'])->assertRedirect('/ar');
    }

    public function test_root_prefers_saved_locale(): void
    {
        $this->withCookie('cm_locale', 'en')->get('/', ['Accept-Language' => 'fr'])->assertRedirect('/en');
    }

    public function test_unknown_locale_is_404(): void
    {
        $this->get('/de')->assertNotFound();
    }

    public function test_every_locale_renders_with_direction(): void
    {
        foreach (['ar' => 'rtl', 'en' => 'ltr', 'fr' => 'ltr', 'hi' => 'ltr', 'ur' => 'rtl', 'zh' => 'ltr'] as $code => $dir) {
            $res = $this->get("/$code")->assertOk();
            $this->assertStringContainsString("dir=\"$dir\"", $res->getContent(), $code);
        }
        $this->assertStringContainsString('lang="ur-PK"', $this->get('/ur')->getContent());
        $this->assertStringContainsString('lang="zh-Hans"', $this->get('/zh')->getContent());
    }

    public function test_home_sections_come_from_the_published_revision_in_approved_order(): void
    {
        $this->get('/en')->assertInertia(fn (Assert $page) => $page
            ->component('ContentPage')
            ->where('sections.0.type', 'hero')
            ->where('sections.0.title', 'One place, endless solutions')
            ->where('sections', fn ($s) => collect($s)->pluck('type')->all() === ['hero', 'whyInvest', 'journey', 'countries', 'sectors', 'trackRecord', 'services', 'map', 'faq', 'cta', 'testimonials'])
            ->where('reviewStatus', 'approved')
            ->has('services', 11)
            ->has('regions', 13)
            ->has('countries', 14));
    }

    public function test_hero_slideshow_sends_eight_landmark_slides_with_large_renditions(): void
    {
        $this->get('/ar')->assertInertia(fn (Assert $page) => $page
            ->has('sections.0.slides', 8)
            ->where('sections.0.slides.0.title', 'مكان واحد، حلول لا تنتهي')
            ->where('sections.0.slides.3.title', 'العلا')
            ->where('sections.0.slides.3.region', 'madinah')
            ->where('sections.0.slides.3.subtitle', fn ($t) => str_contains($t, 'الإقامة والضيافة'))
            ->where('media.hero-alula-elephant-rock.srcset', fn ($s) => str_contains($s, '/media/hero/hero-alula-elephant-rock-2560.webp 2560w'))
            ->where('media.hero-alula-elephant-rock.alt', 'صخرة الفيل مضاءة ليلاً، العلا'));

        foreach (['1280', '1920', '2560'] as $w) {
            $this->assertFileExists(public_path("media/hero/hero-neom-islands-{$w}.webp"));
        }
    }

    public function test_arabic_content_is_flagged_until_reviewed(): void
    {
        $this->get('/ar')->assertInertia(fn (Assert $page) => $page
            ->where('sections.0.title', 'مكان واحد، حلول لا تنتهي')
            ->where('reviewStatus', 'review'));
    }

    public function test_shared_interface_props_follow_the_url_locale(): void
    {
        $this->get('/ar')->assertInertia(fn (Assert $page) => $page
            ->where('locale.code', 'ar')
            ->where('locale.dir', 'rtl')
            ->where('site.cta.label', 'احصل على استشارة')
            ->where('site.nav.0.label', 'من نحن'));
        $this->get('/zh')->assertInertia(fn (Assert $page) => $page->where('locale.tag', 'zh-Hans')->where('site.nav.0.label', 'About'));
    }

    public function test_translation_notice_is_off_by_owner_decision(): void
    {
        $this->get('/fr')->assertInertia(fn (Assert $page) => $page->where('site.translationNotice', false));
    }

    public function test_untranslated_locale_falls_back_to_english_with_notice(): void
    {
        $this->get('/fr')->assertInertia(fn (Assert $page) => $page
            ->where('sections.0.title', 'One place, endless solutions')
            ->where('reviewStatus', 'missing'));
    }

    public function test_draft_changes_do_not_reach_the_live_page_until_published(): void
    {
        $page = Page::where('key', 'home')->first();
        $draft = $page->draft_sections;
        $draft[0]['title']['en'] = 'Draft headline';
        $page->update(['draft_sections' => $draft]);

        $this->get('/en')->assertInertia(fn (Assert $p) => $p->where('sections.0.title', 'One place, endless solutions'));

        (new PublishPage)($page->fresh());
        $this->get('/en')->assertInertia(fn (Assert $p) => $p->where('sections.0.title', 'Draft headline'));
        $this->assertSame(2, $page->revisions()->count());
    }

    public function test_hidden_sections_are_absent(): void
    {
        $page = Page::where('key', 'home')->first();
        $draft = $page->draft_sections;
        $draft[1]['hidden'] = true; // whyInvest
        $page->update(['draft_sections' => $draft]);
        (new PublishPage)($page->fresh());

        $res = $this->get('/en');
        $res->assertInertia(fn (Assert $p) => $p->where('sections.1.type', 'journey'));
        $this->assertStringNotContainsString('fastest-transforming', $res->getContent());
    }

    public function test_rule_01_unverified_figures_are_never_sent(): void
    {
        // The rule itself: figures marked unverified never reach the page (D-09 approved the seeded ones).
        Stat::whereNotIn('key', ['companies-established', 'years-experience'])->update(['verified' => false]);
        $html = $this->get('/en')->getContent();
        $this->assertStringContainsString('1,000+', $html);
        foreach (['500+', '64.7%', '4.8 trillion', '157', '209'] as $figure) {
            $this->assertStringNotContainsString($figure, $html, $figure);
        }
        $this->get('/en')->assertInertia(fn (Assert $p) => $p->has('stats', 2)->where('stats.0.value', '1,000+'));

        Stat::where('key', 'companies-established')->update(['verified' => false]);
        Stat::where('key', 'years-experience')->update(['verified' => false]);
        $this->get('/en')->assertInertia(fn (Assert $p) => $p->has('stats', 0)->where('sections.5.type', 'services'));
    }

    public function test_rule_02_testimonial_names_need_consent(): void
    {
        Testimonial::query()->update(['consent_recorded_at' => null]);
        Testimonial::first()->translations()->where('locale', 'en')->update(['author' => 'Jane Example']);
        $this->assertStringNotContainsString('Jane Example', $this->get('/en')->getContent());

        Testimonial::first()->update(['consent_recorded_at' => now()]);
        $this->get('/en')->assertInertia(fn (Assert $p) => $p->where('testimonials.0.author', 'Jane Example'));
    }

    public function test_faq_preview_excludes_items_needing_review(): void
    {
        $this->get('/en')->assertInertia(fn (Assert $p) => $p->has('faqs', 6));
        $flagged = Faq::where('needs_review', true)->with('translations')->get()->map->t('question', 'en');
        $html = $this->get('/en')->getContent();
        foreach ($flagged as $q) {
            $this->assertStringNotContainsString(e($q), $html);
        }
    }

    public function test_contact_settings_are_owner_supplied_only(): void
    {
        $this->get('/en')->assertInertia(fn (Assert $p) => $p
            ->has('site.offices', 2)
            ->where('site.email', null)
            ->where('site.phone', null)
            ->where('site.social.instagram', 'https://www.instagram.com/creative_mark.co/'));
    }
}
