<?php

namespace Tests\Feature;

use App\Models\CaseStudy;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CaseStudiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ContentSeeder::class);
    }

    public function test_listing_shows_published_cases_in_order(): void
    {
        $this->get('/en/case-studies')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('ContentPage')
            ->where('sections', fn ($s) => collect($s)->pluck('type')->all() === ['hero', 'cards', 'caseGrid', 'cta'])
            ->has('cases', 5)
            ->where('cases.0.slug', 'manufacturer-special-economic-zone')
            ->missing('cases.0.steps')
            ->has('media.case-factory'));
    }

    public function test_seeded_cases_are_marked_illustrative_and_name_no_client(): void
    {
        $this->assertSame(0, CaseStudy::where('illustrative', false)->count());
        $this->get('/en/case-studies')->assertInertia(fn (Assert $page) => $page
            ->where('cases', fn ($c) => collect($c)->every(fn ($x) => $x['illustrative'] === true)));
        // The hero must not claim real results for illustrative examples.
        $this->assertStringNotContainsString('Real results', $this->get('/en/case-studies')->getContent());
    }

    public function test_every_case_has_a_page_in_every_locale(): void
    {
        foreach (CaseStudy::pluck('slug') as $slug) {
            foreach (['ar', 'en', 'fr', 'hi', 'ur', 'zh'] as $locale) {
                $this->get("/{$locale}/case-studies/{$slug}")->assertOk();
            }
        }
    }

    public function test_case_page_tells_the_full_story(): void
    {
        $this->get('/en/case-studies/logistics-group-acquisition')->assertInertia(fn (Assert $page) => $page
            ->component('CaseStudyPage')
            ->where('caseStudy.client', 'A logistics group from Malaysia')
            ->has('caseStudy.steps', 5)
            ->where('caseStudy.steps.1.title', 'Due diligence')
            ->has('caseStudy.delivered', 6)
            ->where('region.slug', 'eastern')
            ->where('related.0.slug', 'international-acquisition')
            ->has('otherCases', 2));

        $this->get('/ar/case-studies/logistics-group-acquisition')->assertInertia(fn (Assert $page) => $page
            ->where('caseStudy.steps.1.title', 'الفحص النافي للجهالة'));
    }

    public function test_draft_unknown_and_misaligned_cases(): void
    {
        CaseStudy::where('slug', 'restaurant-brand-jeddah')->update(['status' => 'draft']);
        $this->get('/en/case-studies/restaurant-brand-jeddah')->assertNotFound();
        $this->get('/en/case-studies/unknown')->assertNotFound();

        CaseStudy::where('slug', 'saudi-trading-company-uae')->first()->translations()->where('locale', 'en')
            ->update(['step_texts' => json_encode(['only one'])]);
        $this->get('/en/case-studies/saudi-trading-company-uae')->assertInertia(fn (Assert $page) => $page->has('caseStudy.steps', 0));
    }
}
