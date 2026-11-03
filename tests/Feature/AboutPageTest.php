<?php

namespace Tests\Feature;

use App\Content\SectionSchema;
use App\Models\Page;
use App\Models\Stat;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ContentSeeder::class);
    }

    public function test_about_page_renders_the_presentation_content_in_order(): void
    {
        $this->get('/en/about')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('ContentPage')
            ->where('pageKey', 'about')
            ->where('title', 'About')
            ->where('sections', fn ($s) => collect($s)->pluck('type')->all() === ['hero', 'statement', 'imageText', 'imageText', 'imageText', 'cards', 'trackRecord', 'cta'])
            ->where('sections.0.title', 'More than company formation')
            ->where('sections.1.title', 'We do not stop at registration. We build the structure behind the business.')
            ->has('sections.5.cards', 6)
            ->where('sections.3.layout', 'split-reverse')
            ->where('seo.title', 'About Creative Mark | More than company formation')
            ->where('media.about-handshake.srcset', fn ($s) => str_contains($s, '/media/about/about-handshake-2560.webp 2560w')));
    }

    public function test_about_page_is_available_in_arabic(): void
    {
        $this->get('/ar/about')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('title', 'من نحن')
            ->where('sections.0.title', 'أكثر من مجرد تأسيس شركات'));
    }

    public function test_about_content_passes_the_section_schema(): void
    {
        SectionSchema::validate(Page::where('key', 'about')->first()->draft_sections);
        $this->assertTrue(true);
    }

    public function test_only_verified_figures_appear_on_about(): void
    {
        // The rule itself: figures marked unverified never reach the page (D-09 approved the seeded ones).
        Stat::whereNotIn('key', ['companies-established', 'years-experience'])->update(['verified' => false]);
        $html = $this->get('/en/about')->getContent();
        $this->assertStringContainsString('1,000+', $html);
        $this->assertStringNotContainsString('500+', $html);
    }

    public function test_invalid_layout_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        SectionSchema::validate([['id' => 'x', 'type' => 'imageText', 'layout' => 'diagonal', 'style' => ['theme' => 'dark']]]);
    }
}
