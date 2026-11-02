<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NewsroomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ContentSeeder::class);
    }

    public function test_newsroom_lists_published_stories_newest_first(): void
    {
        $this->get('/en/news')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('ContentPage')
            ->where('sections', fn ($s) => collect($s)->pluck('type')->all() === ['newsLead', 'newsGrid', 'cta'])
            ->has('articles', 9)
            ->where('articles', fn ($a) => collect($a)->pluck('date')->all() === collect($a)->pluck('date')->sortDesc()->values()->all())
            ->where('articles.0.source.url', fn ($u) => str_starts_with($u, 'https://'))
            ->missing('articles.0.body')
            ->has('media.news-riyadh-night'));
    }

    public function test_every_story_has_a_page_in_every_locale(): void
    {
        foreach (NewsArticle::pluck('slug') as $slug) {
            foreach (['ar', 'en', 'fr', 'hi', 'ur', 'zh'] as $locale) {
                $this->get("/{$locale}/news/{$slug}")->assertOk();
            }
        }
    }

    public function test_story_page_shows_summary_takeaway_facts_source_and_related(): void
    {
        $this->get('/en/news/fdi-inflows-rise-33-percent-2025')->assertInertia(fn (Assert $page) => $page
            ->component('NewsArticlePage')
            ->where('article.title', 'Foreign direct investment into Saudi Arabia rose by a third in 2025')
            ->where('article.facts.0', ['value' => 'SAR 159.1bn', 'label' => 'FDI inflows in 2025'])
            ->where('article.source.name', 'Arab News')
            ->where('article.takeaway', fn ($t) => filled($t))
            ->has('related', 3)
            ->where('related', fn ($r) => ! in_array('fdi-inflows-rise-33-percent-2025', array_column(collect($r)->all(), 'slug'), true)));

        $this->get('/ar/news/fdi-inflows-rise-33-percent-2025')->assertInertia(fn (Assert $page) => $page
            ->where('article.facts.0.value', '159.1 مليار ريال'));
    }

    public function test_draft_and_unknown_stories_are_hidden(): void
    {
        NewsArticle::where('slug', 'zatca-e-invoicing-inspections')->update(['status' => 'draft']);
        $this->get('/en/news/zatca-e-invoicing-inspections')->assertNotFound();
        $this->get('/en/news/unknown')->assertNotFound();
        $this->get('/en/news')->assertInertia(fn (Assert $page) => $page->has('articles', 8));
    }

    public function test_misaligned_facts_are_not_shown(): void
    {
        $article = NewsArticle::where('slug', 'us-investment-licences-2026')->first();
        $article->translations()->where('locale', 'en')->update(['fact_labels' => json_encode(['only one'])]);
        $this->get('/en/news/us-investment-licences-2026')->assertInertia(fn (Assert $page) => $page->has('article.facts', 0));
    }
}
