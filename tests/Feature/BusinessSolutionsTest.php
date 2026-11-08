<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\Service;
use App\Models\Stat;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BusinessSolutionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ContentSeeder::class);
    }

    public function test_solutions_page_groups_all_eleven_services(): void
    {
        $this->get('/en/services')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('ContentPage')
            ->where('pageKey', 'services')
            ->where('sections', fn ($s) => collect($s)->pluck('type')->all() === ['hero', 'statement', 'serviceGroups', 'processSteps', 'trackRecord', 'cta'])
            ->has('services', 11)
            ->where('services', fn ($s) => collect($s)->countBy('group')->all() === ['setup' => 3, 'grow' => 5, 'comply' => 3])
            ->where('serviceGroups.comply', 'Comply')
            ->has('journey', 7)
            ->where('journey.6.title', 'Growth'));
    }

    public function test_every_service_has_a_detail_page_in_every_locale(): void
    {
        foreach (Service::pluck('slug') as $slug) {
            foreach (['ar', 'en', 'fr', 'hi', 'ur', 'zh'] as $locale) {
                $this->get("/{$locale}/services/{$slug}")->assertOk();
            }
        }
    }

    public function test_service_page_shows_overview_scope_related_and_faqs(): void
    {
        // Each solution is an editable page built from its service record, with the original layout.
        $this->get('/en/services/company-formation')->assertInertia(fn (Assert $page) => $page
            ->component('ContentPage')
            ->where('pageKey', 'service-company-formation')
            ->where('sections', fn ($s) => collect($s)->pluck('type')->all() === ['hero', 'serviceOverview', 'processSteps', 'faq', 'caseGrid', 'relatedServices', 'cta'])
            ->where('sections.0.title', 'Company Formation')
            ->has('sections.1.cards', 11)
            ->where('sections.1.note', fn ($n) => str_contains($n, '1,000'))
            ->where('sections.0.buttons.0.href', '/consultation?service=company-formation')
            ->has('related', 3)
            ->has('journey', 7)
            ->where('faqs', fn ($f) => count($f) >= 1)
            ->where('breadcrumbs.1.label', 'Company Formation')
            ->where('media.svc-company-formation.srcset', fn ($s) => str_contains($s, '/media/services/svc-company-formation-2560.webp')));

        $this->get('/ar/services/tax-and-budgeting')->assertInertia(fn (Assert $page) => $page
            ->where('sections.0.title', 'الضرائب والميزانيات')
            ->where('sections.1.cards.0.title', 'التسجيل الضريبي')
            ->where('sections', fn ($s) => ! collect($s)->contains('type', 'processSteps')));
    }

    public function test_service_faqs_never_include_items_needing_review(): void
    {
        Faq::query()->update(['needs_review' => true]);
        $this->get('/en/services/company-formation')->assertInertia(fn (Assert $page) => $page
            ->has('faqs', 0)->where('sections', fn ($s) => ! collect($s)->contains('type', 'faq')));
    }

    public function test_unverified_figures_from_the_presentation_are_not_shown(): void
    {
        // The rule itself: figures marked unverified never reach the page (D-09 approved the seeded ones).
        Stat::whereNotIn('key', ['companies-established', 'years-experience'])->update(['verified' => false]);
        Service::where('slug', 'business-consulting')->update(['needs_review' => true]);
        $html = $this->get('/en/services/licensing-and-government-approvals')->getContent().$this->get('/en/services')->getContent();
        // The asset version is a hash and may contain any digits.
        $html = preg_replace('/"version":"[0-9a-f]+"/', '', $html);
        foreach (['500+', '209', '157', '144', '103', '4.8 trillion'] as $figure) {
            $this->assertStringNotContainsString($figure, $html, $figure);
        }
    }

    public function test_draft_or_unknown_services_are_not_found(): void
    {
        Service::where('slug', 'office-solutions')->update(['status' => 'draft']);
        $this->get('/en/services/office-solutions')->assertNotFound();
        $this->get('/en/services/unknown')->assertNotFound();
        $this->get('/en/services/Bad_Slug')->assertNotFound();
    }
}
