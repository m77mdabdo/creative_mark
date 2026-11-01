<?php

namespace Tests\Feature;

use App\Content\SectionSchema;
use App\Models\Page;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvestorGuideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ContentSeeder::class);
    }

    public function test_guide_renders_eight_chapters_checklist_and_sources(): void
    {
        $this->get('/en/investor-guide')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('ContentPage')
            ->where('pageKey', 'investor-guide')
            ->where('sections', fn ($s) => collect($s)->pluck('type')->all() === [
                'hero', 'guideNav', ...array_fill(0, 8, 'guideChapter'), 'checklist', 'sourceList', 'cta',
            ])
            ->has('sections.10.cards', 12)
            ->where('sections.6.cards.0.title', '20%')
            ->where('sections.11.sources', fn ($s) => count($s) >= 25)
            ->where('media.guide-metro.srcset', fn ($s) => str_contains($s, '/media/guide/guide-metro-2560.webp')));
    }

    public function test_every_chapter_cites_https_sources(): void
    {
        $chapters = collect(Page::where('key', 'investor-guide')->first()->publishedRevision->sections)->where('type', 'guideChapter');
        $this->assertCount(8, $chapters);
        foreach ($chapters as $chapter) {
            $this->assertNotEmpty($chapter['sources'], $chapter['id']);
            foreach ($chapter['sources'] as $source) {
                $this->assertStringStartsWith('https://', $source['href']);
            }
        }
    }

    public function test_guide_is_available_in_every_locale_with_localized_text(): void
    {
        foreach (['ar', 'fr', 'hi', 'ur', 'zh'] as $locale) {
            $this->get("/{$locale}/investor-guide")->assertOk();
        }
        $this->get('/ar/investor-guide')->assertInertia(fn (Assert $page) => $page
            ->where('sections.2.title', 'سوق يعيد كتابة قواعده')
            ->where('sections.3.sources.0.publisher', 'وزارة الاستثمار'));
    }

    public function test_sources_must_be_secure_links(): void
    {
        $section = ['id' => 'c1', 'type' => 'guideChapter', 'style' => ['theme' => 'dark'], 'motion' => ['preset' => 'fade-up'],
            'sources' => [['id' => 's1', 'label' => ['en' => 'x'], 'href' => 'javascript:alert(1)']]];
        $this->expectException(ValidationException::class);
        SectionSchema::validate([$section]);
    }

    public function test_source_fields_are_allow_listed_and_text_only(): void
    {
        foreach ([
            ['id' => 's1', 'label' => ['en' => 'x'], 'href' => 'https://example.com', 'onclick' => 'x'],
            ['id' => 's1', 'label' => ['en' => '<b>x</b>'], 'href' => 'https://example.com'],
            ['id' => 's1', 'label' => ['en' => 'x'], 'href' => 'http://example.com'],
        ] as $source) {
            try {
                SectionSchema::validate([['id' => 'c1', 'type' => 'guideChapter', 'style' => ['theme' => 'dark'], 'motion' => ['preset' => 'fade-up'], 'sources' => [$source]]]);
                $this->fail('Invalid source accepted.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
