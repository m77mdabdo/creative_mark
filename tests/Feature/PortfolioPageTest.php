<?php

namespace Tests\Feature;

use App\Content\PublishPage;
use App\Content\SectionSchema;
use App\Enums\Role;
use App\Filament\Pages\EditPortfolioPage;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

/** Our portfolio (سابقة الأعمال): client strip, years and achievements, sectors; menu entry; dashboard editing. */
class PortfolioPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RbacSeeder::class, ContentSeeder::class]);
    }

    private function manager(): User
    {
        return User::factory()->withRole(Role::ContentManager)->create();
    }

    public function test_page_renders_in_every_language_and_is_in_the_menu_after_our_edge(): void
    {
        foreach (['ar', 'en', 'fr', 'hi', 'ur', 'zh'] as $locale) {
            $this->get("/{$locale}/portfolio")->assertOk()->assertInertia(fn (Assert $p) => $p
                ->component('ContentPage')->where('pageKey', 'portfolio')->where('title', fn ($t) => filled($t))
                ->where('site.nav', fn ($nav) => collect($nav)->pluck('href')->search('/portfolio') === collect($nav)->pluck('href')->search('/our-edge') + 1));
        }
        $this->get('/ar/portfolio')->assertInertia(fn (Assert $p) => $p->where('title', 'سابقة الأعمال')
            ->where('site.nav', fn ($nav) => collect($nav)->contains('label', 'سابقة الأعمال')));
        $this->get('/sitemap.xml')->assertOk()->assertSee('/ar/portfolio');
    }

    public function test_unconfirmed_content_stays_off_the_site(): void
    {
        // Seeded clients and years are placeholders (no consent, not confirmed): the client strip is left out,
        // the years section keeps only the published statistics, and sectors show without figures.
        $this->get('/en/portfolio')->assertInertia(fn (Assert $p) => $p
            ->where('sections', fn ($s) => collect($s)->pluck('type')->all() === ['hero', 'milestones', 'sectorWork', 'cta'])
            ->where('sections.1.cards', [])
            ->where('stats', fn ($stats) => collect($stats)->contains('value', '1,000+') && collect($stats)->contains('key', 'years-experience'))
            ->has('sections.2.cards', 14)
            ->where('sections.2.cards', fn ($cards) => collect($cards)->every(fn ($c) => ! array_key_exists('value', (array) $c))));
    }

    public function test_dashboard_edits_clients_videos_links_and_sector_figures(): void
    {
        $user = $this->manager();
        $this->actingAs($user)->get('/admin/pages/portfolio')->assertOk();
        $component = Livewire::actingAs($user)->test(EditPortfolioPage::class)->assertActionVisible('manageStats');
        $sections = $component->get('data.sections');
        $clients = collect($sections)->search(fn ($s) => $s['type'] === 'clientMarquee');
        $sectors = collect($sections)->search(fn ($s) => $s['type'] === 'sectorWork');

        $first = array_key_first($sections[$clients]['cards']);
        $sections[$clients]['cards'][$first] = [...$sections[$clients]['cards'][$first], 'verified' => true, 'href' => '/case-studies',
            'video_url' => 'https://cdn.example.com/clips/client.mp4', 'title' => ['en' => 'Acme Arabia', 'ar' => 'أكمي العربية']];
        $industry = array_key_first($sections[$sectors]['cards']);
        $sections[$sectors]['cards'][$industry] = [...$sections[$sectors]['cards'][$industry], 'verified' => true, 'value' => '120+'];
        $component->set('data.sections', $sections)->call('save')->assertHasNoErrors()->callAction('publish');

        $this->get('/ar/portfolio')->assertInertia(fn (Assert $p) => $p
            ->where('sections.1.type', 'clientMarquee')
            ->has('sections.1.cards', 1)
            ->where('sections.1.cards.0.title', 'أكمي العربية')
            ->where('sections.1.cards.0.href', '/case-studies')
            ->where('sections.1.cards.0.video', 'https://cdn.example.com/clips/client.mp4')
            ->where('sections.3.cards.0.value', '120+')
            ->where('sections.3.note', 'شركة أسسناها'));

        // Saving again keeps the linked video in its own field.
        Livewire::actingAs($user)->test(EditPortfolioPage::class)->call('save')->assertHasNoErrors();
        $saved = collect(Page::where('key', 'portfolio')->first()->draft_sections)->firstWhere('type', 'clientMarquee')['cards'][0];
        $this->assertSame('https://cdn.example.com/clips/client.mp4', $saved['video']);
        $this->assertArrayNotHasKey('video_url', $saved);
    }

    public function test_uploaded_video_paths_are_served_from_the_uploads_folder(): void
    {
        $page = Page::where('key', 'portfolio')->first();
        $sections = $page->draft_sections;
        $i = collect($sections)->search(fn ($s) => $s['type'] === 'clientMarquee');
        $sections[$i]['cards'][0] = [...$sections[$i]['cards'][0], 'verified' => true, 'video' => 'videos/01JABCDEF.mp4'];
        $page->forceFill(['draft_sections' => $sections])->save();
        (new PublishPage)($page);

        $this->get('/en/portfolio')->assertInertia(fn (Assert $p) => $p->where('sections.1.cards.0.video', '/media/uploads/videos/01JABCDEF.mp4'));
    }

    public function test_schema_rejects_unsafe_links_videos_and_figures(): void
    {
        $base = fn (array $card) => [['id' => 'c', 'type' => 'clientMarquee', 'style' => ['theme' => 'dark'], 'cards' => [['id' => 'x', ...$card]]]];
        foreach ([['href' => 'javascript:alert(1)'], ['href' => '//evil.example'], ['video' => '../../.env'], ['video' => 'http://example.com/a.mp4'],
            ['video' => 'https://example.com/a.exe'], ['video' => 'videos/a.php'], ['value' => '<b>9</b>'], ['value' => 'many']] as $bad) {
            try {
                SectionSchema::validate($base($bad));
                $this->fail('Accepted '.json_encode($bad));
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        SectionSchema::validate($base(['href' => 'https://example.com', 'video' => 'videos/01JABC.webm', 'value' => '1,200+']));
    }

    public function test_migration_adds_the_menu_entry_once_and_keeps_edited_menus(): void
    {
        $header = Setting::where('group', 'navigation')->where('key', 'header')->first();
        $header->update(['value' => array_values(array_filter($header->value, fn ($i) => $i['href'] !== '/portfolio'))]);
        Page::where('key', 'portfolio')->first()->delete();
        $migration = require database_path('migrations/2026_11_24_150000_add_portfolio_page.php');
        $migration->up();
        $migration->up();
        $hrefs = collect(Setting::where('group', 'navigation')->where('key', 'header')->first()->value)->pluck('href');
        $this->assertSame(1, $hrefs->filter(fn ($h) => $h === '/portfolio')->count());
        $this->assertSame($hrefs->search('/our-edge') + 1, $hrefs->search('/portfolio'));
        $this->assertSame(1, Page::where('key', 'portfolio')->count());
        $this->get('/en/portfolio')->assertOk();
    }
}
