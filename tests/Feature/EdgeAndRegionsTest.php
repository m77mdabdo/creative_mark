<?php

namespace Tests\Feature;

use App\Content\SectionSchema;
use App\Models\Region;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EdgeAndRegionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ContentSeeder::class);
    }

    public function test_our_edge_page_renders_its_sections_in_order(): void
    {
        $this->get('/en/our-edge')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('ContentPage')
            ->where('pageKey', 'our-edge')
            ->where('sections', fn ($s) => collect($s)->pluck('type')->all() === ['hero', 'statement', 'comparison', 'cards', 'countries', 'processSteps', 'trackRecord', 'cta'])
            ->where('sections.2.cards', fn ($c) => count($c) === 2 && substr_count($c[1]['text'], "\n") === 4)
            ->has('sections.3.cards', 6));

        foreach (['ar', 'fr', 'hi', 'ur', 'zh'] as $locale) {
            $this->get("/{$locale}/our-edge")->assertOk();
        }
    }

    public function test_regions_page_lists_all_thirteen_regions(): void
    {
        $this->get('/en/regions')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('pageKey', 'regions')
            ->where('sections', fn ($s) => collect($s)->pluck('type')->all() === ['hero', 'map', 'regionGrid', 'cta'])
            ->has('regions', 13)
            ->where('media.reg-hail.srcset', fn ($s) => str_contains($s, '/media/regions/reg-hail-2560.webp')));
    }

    public function test_every_region_has_a_page_in_every_locale(): void
    {
        foreach (Region::pluck('slug') as $slug) {
            foreach (['ar', 'en', 'fr', 'hi', 'ur', 'zh'] as $locale) {
                $this->get("/{$locale}/regions/{$slug}")->assertOk();
            }
        }
    }

    public function test_region_page_shows_the_region_its_services_and_three_more_regions(): void
    {
        $this->get('/en/regions/hail')->assertInertia(fn (Assert $page) => $page
            ->component('RegionPage')
            ->where('region.slug', 'hail')
            ->where('region.media', 'reg-hail')
            ->where('related', fn ($r) => count($r) >= 1)
            ->has('otherRegions', 3)
            ->where('otherRegions', fn ($r) => ! in_array('hail', array_column(collect($r)->all(), 'slug'), true))
            ->has('media.reg-hail'));

        $this->get('/ar/regions/riyadh')->assertInertia(fn (Assert $page) => $page->where('region.name', 'الرياض'));
    }

    public function test_draft_or_unknown_regions_are_not_found(): void
    {
        Region::where('slug', 'tabuk')->update(['status' => 'draft']);
        $this->get('/en/regions/tabuk')->assertNotFound();
        $this->get('/en/regions/unknown')->assertNotFound();
        $this->get('/en/regions')->assertInertia(fn (Assert $page) => $page->has('regions', 12));
    }

    public function test_new_section_types_are_accepted_by_the_schema(): void
    {
        $this->assertContains('comparison', SectionSchema::TYPES);
        $this->assertContains('regionGrid', SectionSchema::TYPES);
    }
}
