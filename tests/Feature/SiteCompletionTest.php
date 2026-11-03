<?php

namespace Tests\Feature;

use App\Content\SectionSchema;
use App\Models\Faq;
use App\Models\Page;
use Database\Seeders\ContentSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Start your company, FAQ, contact and legal pages, the sitemap, robots.txt and the branded error page. */
class SiteCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RbacSeeder::class, ContentSeeder::class]);
    }

    public function test_new_pages_render_in_every_language(): void
    {
        foreach (['start-your-company', 'faq', 'contact', 'privacy', 'terms'] as $key) {
            foreach (['en', 'ar', 'fr', 'hi', 'ur', 'zh'] as $locale) {
                $this->get("/{$locale}/{$key}")->assertOk()->assertInertia(fn (Assert $page) => $page->component('ContentPage')->where('pageKey', $key));
            }
        }
    }

    public function test_start_page_links_the_booking_form_to_company_formation(): void
    {
        $this->get('/en/start-your-company')->assertInertia(fn (Assert $page) => $page
            ->where('sections.0.buttons.0.href', '/consultation?service=company-formation')
            ->where('sections', fn ($sections) => collect($sections)->where('type', 'guideChapter')->count() === 3));
        $this->get('/en/consultation?service=company-formation')->assertInertia(fn (Assert $page) => $page->where('prefill.service', 'company-formation'));
    }

    public function test_faq_library_lists_every_reviewed_question_with_categories(): void
    {
        $expected = Faq::where('status', 'published')->where('needs_review', false)->count();
        $this->get('/en/faq')->assertInertia(fn (Assert $page) => $page
            ->has('faqLibrary.items', $expected)
            ->has('faqLibrary.categories')
            ->where('faqLibrary.items', fn ($items) => collect($items)->every(fn ($f) => filled($f['category']))));
    }

    public function test_legal_text_allows_long_plain_text_but_no_html(): void
    {
        $page = Page::where('key', 'privacy')->first();
        SectionSchema::validate($page->draft_sections);

        $this->expectException(ValidationException::class);
        SectionSchema::validate([[...$page->draft_sections[1], 'body' => ['en' => '<script>x</script>']]]);
    }

    public function test_sitemap_lists_pages_in_all_languages_with_alternates(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        foreach (['/ar/start-your-company', '/zh/faq', '/fr/privacy', '/en/services/company-formation', '/ur/consultation'] as $path) {
            $this->assertStringContainsString(config('app.url').$path.'</loc>', $xml);
        }
        $this->assertStringContainsString('hreflang="x-default"', $xml);
        $this->assertStringNotContainsString('/admin', $xml);
    }

    public function test_robots_blocks_everything_outside_production(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
    }

    public function test_unknown_pages_get_the_branded_error_page_in_the_right_language(): void
    {
        $this->get('/ar/does-not-exist')->assertNotFound()->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')->where('status', 404)->where('locale.code', 'ar'));
        $this->get('/fr/services/unknown')->assertNotFound()->assertInertia(fn (Assert $page) => $page->component('ErrorPage')->where('locale.code', 'fr'));
        $this->getJson('/en/does-not-exist')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_footer_links_resolve(): void
    {
        foreach (['/en/faq', '/en/contact', '/en/privacy', '/en/terms', '/en/start-your-company'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
