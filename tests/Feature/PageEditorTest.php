<?php

namespace Tests\Feature;

use App\Content\MediaLibrary;
use App\Enums\Role;
use App\Filament\Pages\EditAboutPage;
use App\Filament\Pages\EditBookingTexts;
use App\Filament\Pages\EditCaseLabels;
use App\Filament\Pages\EditContactSettings;
use App\Filament\Pages\EditErrorTexts;
use App\Filament\Pages\EditHomePage;
use App\Filament\Pages\EditInvestorGuidePage;
use App\Filament\Pages\EditJourney;
use App\Filament\Pages\EditNavigationSettings;
use App\Filament\Pages\EditNewsLabels;
use App\Filament\Pages\EditNewsPage;
use App\Filament\Pages\EditOurEdgePage;
use App\Filament\Pages\EditPrivacyPage;
use App\Filament\Pages\EditRegionPage;
use App\Filament\Pages\EditServicePage;
use App\Filament\Pages\EditServicesPage;
use App\Filament\Resources\CaseStudies\Pages\EditCaseStudy;
use App\Filament\Resources\NewsArticles\Pages\EditNewsArticle;
use App\Filament\Resources\NewsArticles\Pages\ListNewsArticles;
use App\Filament\Support\IconPicker;
use App\Http\Middleware\EnsurePrivilegedRolesUseMfa;
use App\Mail\ScheduledStoryPublished;
use App\Models\CaseStudy;
use App\Models\NewsArticle;
use App\Models\Page;
use App\Models\Region;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\Testimonial;
use App\Models\User;
use App\News\AnnouncePublishedStories;
use App\Translation\UiStrings;
use Database\Seeders\ContentSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

/** Home page editor: reorder, hide, edit, save, preview and publish, with permissions and edit conflicts. */
class PageEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RbacSeeder::class, ContentSeeder::class]);
        $this->withoutMiddleware(EnsurePrivilegedRolesUseMfa::class);
    }

    private function manager(): User
    {
        return User::factory()->withRole(Role::ContentManager)->create();
    }

    private function home(): Page
    {
        return Page::where('key', 'home')->with('publishedRevision')->first();
    }

    public function test_editor_opens_with_every_section(): void
    {
        $this->actingAs($this->manager())->get('/admin/pages/home')->assertOk();
        $count = count($this->home()->draft_sections);
        $component = Livewire::actingAs($this->manager())->test(EditHomePage::class);
        $this->assertCount($count, $component->get('data.sections'));
    }

    public function test_reorder_hide_and_edit_then_publish(): void
    {
        $user = $this->manager();
        $component = Livewire::actingAs($user)->test(EditHomePage::class);
        $sections = $component->get('data.sections');
        $keys = array_keys($sections);

        // Move the first section after the second, hide the second and rename the hero title in Arabic
        [$a, $b] = [$keys[0], $keys[1]];
        $reordered = [$b => $sections[$b], $a => $sections[$a]] + $sections;
        $reordered[$b]['visible'] = false;
        $reordered[$a]['title']['ar'] = 'عنوان جديد للواجهة';
        $component->set('data.sections', $reordered)->call('save')->assertNotified();

        $draft = $this->home()->draft_sections;
        $this->assertSame('whyInvest', $draft[0]['type']);
        $this->assertTrue($draft[0]['hidden']);
        $this->assertSame('hero', $draft[1]['type']);
        $this->assertSame('عنوان جديد للواجهة', $draft[1]['title']['ar']);

        // Not live until published
        $this->get('/ar')->assertInertia(fn (Assert $p) => $p->where('sections.0.type', 'hero'));
        $component->callAction('publish');
        $this->get('/ar')->assertInertia(fn (Assert $p) => $p
            ->where('sections.0.type', 'hero')->where('sections.0.title', 'عنوان جديد للواجهة')
            ->where('sections', fn ($s) => collect($s)->doesntContain('type', 'whyInvest')));
    }

    public function test_preview_shows_the_draft_to_editors_only(): void
    {
        $page = $this->home();
        $draft = $page->draft_sections;
        $draft[0]['title']['en'] = 'Draft only headline';
        $page->forceFill(['draft_sections' => $draft])->save();

        $this->get('/preview/home/en')->assertRedirect();
        $this->actingAs(User::factory()->withRole(Role::SalesAgent)->create())->get('/preview/home/en')->assertForbidden();
        $this->actingAs($this->manager())->get('/preview/home/en')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('preview', true)->where('sections.0.title', 'Draft only headline'));
        $this->get('/en')->assertInertia(fn (Assert $p) => $p->where('sections.0.title', fn ($t) => $t !== 'Draft only headline'));
    }

    public function test_invalid_content_is_not_saved(): void
    {
        $component = Livewire::actingAs($this->manager())->test(EditHomePage::class);
        $sections = $component->get('data.sections');
        $first = array_key_first($sections);
        $sections[$first]['title']['en'] = '<b>bold</b>';
        $before = $this->home()->draft_sections;
        $component->set('data.sections', $sections)->call('save');
        $this->assertSame($before, $this->home()->draft_sections);
    }

    public function test_editors_save_but_cannot_publish_and_sales_cannot_open(): void
    {
        $editor = User::factory()->withRole(Role::Editor)->create();
        Livewire::actingAs($editor)->test(EditHomePage::class)->assertActionHidden('publish')->assertActionVisible('save');
        $this->actingAs(User::factory()->withRole(Role::SalesAgent)->create())->get('/admin/pages/home')->assertForbidden();
    }

    public function test_uploaded_images_are_reencoded_and_registered(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'img');
        $img = imagecreatetruecolor(1000, 600);
        imagepng($img, $file);
        $key = MediaLibrary::store($file, 'Riyadh office', 'مكتب الرياض');

        $this->assertMatchesRegularExpression('/^up-\d{6}-[a-z0-9]{6}$/', $key);
        $this->assertFileExists(public_path("media/uploads/{$key}-800.webp"));
        $this->assertFileDoesNotExist(public_path("media/uploads/{$key}-1600.webp")); // never upscaled
        $this->assertSame('/media/uploads/'.$key.'-800.webp', MediaLibrary::url($key));
        $this->assertArrayHasKey($key, MediaLibrary::options());

        $bad = tempnam(sys_get_temp_dir(), 'bad');
        file_put_contents($bad, '<?php echo 1;');
        try {
            MediaLibrary::store($bad, 'x');
            $this->fail('A non-image was accepted');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        } finally {
            array_map('unlink', glob(public_path("media/uploads/{$key}-*.webp")));
        }
    }

    public function test_seo_texts_are_saved_per_language(): void
    {
        $component = Livewire::actingAs($this->manager())->test(EditHomePage::class);
        $component->set('data.seo.ar.seo_title', 'عنوان جوجل الجديد')->set('data.seo.en.seo_description', 'New Google description')->call('save');
        $this->get('/ar')->assertInertia(fn (Assert $p) => $p->where('seo.title', 'عنوان جوجل الجديد'));
        $this->get('/en')->assertInertia(fn (Assert $p) => $p->where('seo.description', 'New Google description'));
    }

    public function test_an_earlier_version_can_be_restored(): void
    {
        $page = $this->home();
        $original = $page->publishedRevision;
        $component = Livewire::actingAs($this->manager())->test(EditHomePage::class);
        $sections = $component->get('data.sections');
        $sections[array_key_first($sections)]['title']['en'] = 'Changed headline';
        $component->set('data.sections', $sections)->call('save');
        $this->assertSame('Changed headline', $this->home()->draft_sections[0]['title']['en']);

        $component->call('restoreRevision', $original->id);
        $this->assertSame($original->sections, $this->home()->draft_sections);
        $component->mountAction('history')->assertSee('#'.$original->id);
    }

    public function test_journey_steps_are_edited_in_every_language(): void
    {
        Livewire::actingAs($this->manager())->test(EditJourney::class)
            ->set('data.steps', [
                ['title' => ['en' => 'Talk', 'ar' => 'حديث'], 'text' => ['en' => 'We listen <b>first</b>', 'ar' => 'نستمع أولًا']],
                ['title' => ['en' => 'Build', 'ar' => 'بناء'], 'text' => ['en' => 'We set it up', 'ar' => 'نؤسس']],
            ])->call('save')->assertHasNoErrors();
        $this->get('/ar/services')->assertInertia(fn (Assert $p) => $p->where('journey.0.title', 'حديث')->where('journey.1.text', 'نؤسس'));
        $this->get('/en/services')->assertInertia(fn (Assert $p) => $p->where('journey.0.text', 'We listen first'));
        $this->actingAs(User::factory()->withRole(Role::SalesAgent)->create())->get('/admin/pages/journey')->assertForbidden();
    }

    public function test_icons_come_from_the_site_set_only(): void
    {
        $icons = IconPicker::icons();
        $this->assertArrayHasKey('Building2', $icons);
        $this->assertStringStartsWith('<svg', $icons['Handshake']);
        $ts = file_get_contents(resource_path('js/components/cmsIcons.ts'));
        foreach (array_keys($icons) as $name) {
            $this->assertStringContainsString($name, $ts);
        }
    }

    public function test_figures_and_reviews_are_managed_from_the_home_editor(): void
    {
        $component = Livewire::actingAs($this->manager())->test(EditHomePage::class);
        $component->mountAction('manageStats');
        $items = $component->get('mountedActions.0.data.items');
        $first = array_key_first($items);
        $items[$first]['value'] = '1,200+';
        $last = array_key_last($items);
        unset($items[$last]); // delete one
        $items['new'] = ['id' => null, 'value' => '99', 'icon' => 'Rocket', 'group' => 'secondary', 'verified' => true, 'source' => 'Owner', 'label' => ['en' => 'New figure', 'ar' => 'رقم جديد']];
        $count = Stat::count();
        $component->set('mountedActions.0.data.items', $items)->callMountedAction()->assertHasNoActionErrors();

        $this->assertSame($count, Stat::count());
        $this->get('/ar')->assertInertia(fn (Assert $p) => $p->where('stats.0.value', '1,200+')
            ->where('stats', fn ($s) => collect($s)->contains('label', 'رقم جديد')));

        $component->mountAction('manageTestimonials');
        $reviews = $component->get('mountedActions.0.data.items');
        $key = array_key_first($reviews);
        $reviews[$key]['published'] = false;
        $component->set('mountedActions.0.data.items', $reviews)->callMountedAction();
        $this->assertSame('draft', Testimonial::orderBy('sort_order')->first()->status);

        $editor = User::factory()->withRole(Role::Editor)->create();
        Livewire::actingAs($editor)->test(EditHomePage::class)->assertActionHidden('manageStats');
    }

    public function test_contact_details_drive_the_whatsapp_button(): void
    {
        $admin = User::factory()->withRole(Role::SuperAdmin)->create();
        Livewire::actingAs($admin)->test(EditContactSettings::class)
            ->set('data.whatsapp', '+966 55 123 4567')->set('data.email', 'info@example.org')->call('save')->assertHasNoErrors();
        $this->get('/ar')->assertInertia(fn (Assert $p) => $p->where('site.whatsapp', '+966 55 123 4567')->where('site.email', 'info@example.org'));

        Livewire::actingAs($admin)->test(EditContactSettings::class)
            ->set('data.whatsapp', '0551234567')->call('save')->assertHasErrors(['data.whatsapp']);
        Livewire::actingAs($admin)->test(EditContactSettings::class)
            ->set('data.social.instagram', 'javascript:alert(1)')->call('save')->assertHasErrors(['data.social.instagram']);
        $this->actingAs(User::factory()->withRole(Role::Editor)->create())->get('/admin/settings/contact')->assertForbidden();
    }

    public function test_about_page_is_edited_like_the_home_page(): void
    {
        $user = $this->manager();
        $this->actingAs($user)->get('/admin/pages/about')->assertOk();
        $component = Livewire::actingAs($user)->test(EditAboutPage::class)->assertActionVisible('manageStats');
        $sections = $component->get('data.sections');
        $keys = array_keys($sections);
        $last = $keys[count($keys) - 1];
        $sections[$last]['title']['ar'] = 'عنوان جديد لمن نحن';
        $moved = [$last => $sections[$last]] + $sections; // move the last section to the top
        $component->set('data.sections', $moved)->call('save')->callAction('publish');

        $this->get('/ar/about')->assertInertia(fn (Assert $p) => $p->where('sections.0.type', 'cta')->where('sections.0.title', 'عنوان جديد لمن نحن'));
        $this->assertSame('hero', Page::where('key', 'home')->first()->publishedRevision->sections[0]['type']); // other pages untouched
    }

    public function test_each_business_solution_is_edited_on_its_own_page(): void
    {
        $user = $this->manager();
        $this->actingAs($user)->get('/admin/pages/services')->assertOk();
        $this->actingAs($user)->get('/admin/pages/services/tax-and-budgeting')->assertOk()->assertSee('الضرائب والميزانيات');
        // Pages › Business solutions opens a sub-menu with every solution
        $this->actingAs($user)->get('/admin/pages/services')->assertSee('/admin/pages/services/office-solutions', false);
        $this->actingAs($user)->get('/admin/pages/services/tax-and-budgeting')->assertSee('/admin/pages/services/office-solutions', false);
        $this->actingAs($user)->get('/admin')->assertDontSee('/admin/pages/services/office-solutions', false);
        $this->actingAs($user)->get('/admin/pages/services/unknown')->assertNotFound();

        $component = Livewire::actingAs($user)->test(EditServicePage::class, ['service' => 'tax-and-budgeting']);
        $sections = $component->get('data.sections');
        $hero = array_key_first($sections);
        $sections[$hero]['title']['ar'] = 'الضرائب بطريقة أبسط';
        $component->set('data.sections', array_reverse($sections, true))->call('save')->assertHasNoErrors();
        $component->callAction('publish');

        $this->get('/ar/services/tax-and-budgeting')->assertInertia(fn (Assert $p) => $p
            ->where('sections.0.type', 'cta')->where('sections', fn ($s) => collect($s)->contains('title', 'الضرائب بطريقة أبسط')));
        $this->get('/ar/services/company-formation')->assertInertia(fn (Assert $p) => $p->where('sections.0.type', 'hero')); // others untouched
        $this->assertStringNotContainsString('/service-', $this->get('/sitemap.xml')->getContent());
    }

    public function test_every_page_editor_saves_its_current_content_unchanged(): void
    {
        // Existing texts (e.g. long Google descriptions) must never block saving a page.
        $user = $this->manager();
        foreach ([EditHomePage::class => [], EditAboutPage::class => [], EditServicesPage::class => [],
            EditServicePage::class => ['service' => 'company-formation']] as $class => $params) {
            Livewire::actingAs($user)->test($class, $params)->call('save')->assertHasNoErrors();
        }
    }

    public function test_solution_pages_show_their_cases_and_sync_back_to_the_service(): void
    {
        // Case studies on a solution's page are only those that used it
        $this->get('/en/services/international-acquisition')->assertInertia(fn (Assert $p) => $p
            ->has('cases', 1)->where('cases.0.slug', 'logistics-group-acquisition'));
        $this->get('/en/services/investment-tours')->assertInertia(fn (Assert $p) => $p
            ->where('sections', fn ($s) => ! collect($s)->contains('type', 'caseGrid')));
        // Every solution now has its own questions
        $this->get('/en/services/feasibility-studies')->assertInertia(fn (Assert $p) => $p->has('faqs', 2));

        // Publishing a solution page updates its card and menu entry
        $component = Livewire::actingAs($this->manager())->test(EditServicePage::class, ['service' => 'office-solutions']);
        $sections = $component->get('data.sections');
        $hero = array_key_first($sections);
        $sections[$hero]['title']['en'] = 'Offices ready for business';
        $sections[$hero]['subtitle']['ar'] = 'مكاتب جاهزة للعمل';
        $component->set('data.sections', $sections)->call('save')->callAction('publish');
        $this->get('/en/services')->assertInertia(fn (Assert $p) => $p->where('services', fn ($all) => collect($all)->contains('title', 'Offices ready for business')));
        $this->get('/ar')->assertInertia(fn (Assert $p) => $p->where('site.megaServices', fn ($m) => collect($m)->contains('tagline', 'مكاتب جاهزة للعمل')));
    }

    public function test_our_edge_comparison_and_reviews_are_editable(): void
    {
        $user = $this->manager();
        $this->actingAs($user)->get('/admin/pages/our-edge')->assertOk();
        $component = Livewire::actingAs($user)->test(EditOurEdgePage::class)
            ->assertActionVisible('manageStats')->assertActionVisible('manageTestimonials');
        $sections = $component->get('data.sections');
        $key = collect($sections)->search(fn ($s) => $s['type'] === 'comparison');
        $card = array_key_last($sections[$key]['cards']);
        $sections[$key]['cards'][$card]['text']['ar'] = "بند أول\nبند ثان";
        $component->set('data.sections', $sections)->call('save')->assertHasNoErrors()->callAction('publish');

        $this->get('/ar/our-edge')->assertInertia(fn (Assert $p) => $p
            ->where('sections', fn ($all) => collect($all)->firstWhere('type', 'comparison')['cards'][1]['text'] === "بند أول\nبند ثان"
                && collect($all)->contains('type', 'testimonials')));
    }

    public function test_each_region_is_edited_on_its_own_page_and_syncs_back(): void
    {
        $user = $this->manager();
        $this->actingAs($user)->get('/admin/pages/regions')->assertOk()->assertSee('/admin/pages/regions/tabuk', false);
        $this->actingAs($user)->get('/admin/pages/regions/asir')->assertOk()->assertSee('/admin/pages/regions/tabuk', false);
        $this->actingAs($user)->get('/admin/pages/regions/unknown')->assertNotFound();

        $this->get('/ar/regions/tabuk')->assertInertia(fn (Assert $p) => $p->component('ContentPage')->where('pageKey', 'region-tabuk')
            ->where('sections', fn ($s) => collect($s)->pluck('type')->all() === ['hero', 'regionOverview', 'relatedServices', 'moreRegions', 'cta'])
            ->where('region.slug', 'tabuk')->where('breadcrumbs.1.label', fn ($l) => filled($l))->has('otherRegions', 12));

        $component = Livewire::actingAs($user)->test(EditRegionPage::class, ['region' => 'tabuk']);
        $sections = $component->get('data.sections');
        $hero = array_key_first($sections);
        $sections[$hero]['title']['ar'] = 'تبوك الجديدة';
        $sections[$hero]['subtitle']['ar'] = 'مشاريع كبرى';
        $component->set('data.sections', $sections)->call('save')->assertHasNoErrors()->callAction('publish');

        $this->get('/ar/regions')->assertInertia(fn (Assert $p) => $p->where('regions', fn ($r) => collect($r)->contains(fn ($x) => $x['name'] === 'تبوك الجديدة' && $x['bestFor'] === 'مشاريع كبرى')));
        $this->assertStringNotContainsString('/region-', $this->get('/sitemap.xml')->getContent());
    }

    public function test_region_pages_show_their_cases_and_capital_syncs_back(): void
    {
        $this->get('/en/regions/eastern')->assertInertia(fn (Assert $p) => $p->has('cases', 1)->where('cases.0.slug', 'logistics-group-acquisition')
            ->where('sections', fn ($s) => collect($s)->contains('type', 'caseGrid')));
        $this->get('/en/regions/tabuk')->assertInertia(fn (Assert $p) => $p->where('sections', fn ($s) => ! collect($s)->contains('type', 'caseGrid')));

        $component = Livewire::actingAs($this->manager())->test(EditRegionPage::class, ['region' => 'eastern']);
        $sections = $component->get('data.sections');
        $hero = array_key_first($sections);
        $sections[$hero]['eyebrow']['en'] = 'Capital: Dammam City';
        $component->set('data.sections', $sections)->call('save')->assertHasNoErrors()->callAction('publish');
        $this->get('/en/regions')->assertInertia(fn (Assert $p) => $p->where('regions', fn ($r) => collect($r)->firstWhere('slug', 'eastern')['capital'] === 'Dammam City'));

        // Every sector already set on a region is offered by the form's list
        $known = array_keys(Setting::group('catalogue')['industries']);
        foreach (Region::all() as $region) {
            $this->assertSame([], array_diff($region->industries ?? [], $known), $region->slug);
        }
        $this->actingAs($this->manager())->get('/admin/regions/'.Region::first()->id.'/edit')->assertOk();
    }

    public function test_guide_chapters_points_notes_and_sources_are_editable(): void
    {
        $user = $this->manager();
        $this->actingAs($user)->get('/admin/pages/investor-guide')->assertOk();
        $component = Livewire::actingAs($user)->test(EditInvestorGuidePage::class);
        $sections = $component->get('data.sections');
        $key = collect($sections)->search(fn ($s) => $s['id'] === 'guide-register');
        $sections[$key]['points']['ar'] = "نقطة أولى\nنقطة ثانية";
        $sections[$key]['note']['ar'] = 'ملاحظة جديدة';
        $source = array_key_first($sections[$key]['sources']);
        $sections[$key]['sources'][$source]['href'] = 'https://example.org/new-law';
        $sections[$key]['sources'][$source]['label']['ar'] = 'مرجع جديد';
        $component->set('data.sections', $sections)->call('save')->assertHasNoErrors()->callAction('publish');

        $this->get('/ar/investor-guide')->assertInertia(fn (Assert $p) => $p->where('sections', function ($all) {
            $chapter = collect($all)->firstWhere('id', 'guide-register');

            return $chapter['points'] === "نقطة أولى\nنقطة ثانية" && $chapter['note'] === 'ملاحظة جديدة'
                && $chapter['sources'][0]['href'] === 'https://example.org/new-law' && $chapter['sources'][0]['label'] === 'مرجع جديد';
        }));

        // Insecure source links are refused
        $sections[$key]['sources'][$source]['href'] = 'http://example.org/insecure';
        $before = $this->home()->newQuery()->where('key', 'investor-guide')->first()->draft_sections;
        $component->set('data.sections', $sections)->call('save');
        $this->assertSame($before, Page::where('key', 'investor-guide')->first()->draft_sections);
    }

    public function test_sections_open_in_a_side_panel_and_keep_everything(): void
    {
        $component = Livewire::actingAs($this->manager())->test(EditInvestorGuidePage::class);
        $sections = $component->get('data.sections');
        $item = collect($sections)->search(fn ($s) => $s['id'] === 'guide-checklist');
        $cards = count($sections[$item]['cards']);

        $component->callAction([['name' => 'editSection', 'context' => ['schemaComponent' => 'form.sections'], 'arguments' => ['item' => $item]]], ['label' => 'Checklist renamed'])
            ->assertHasNoActionErrors();
        $this->assertSame('Checklist renamed', $component->get('data.sections')[$item]['label']);
        $component->call('save')->assertHasNoErrors();

        $saved = collect(Page::where('key', 'investor-guide')->first()->draft_sections)->firstWhere('id', 'guide-checklist');
        $this->assertSame('Checklist renamed', $saved['label']);
        $this->assertCount($cards, $saved['cards']);
        foreach ($saved['cards'] as $card) {
            $this->assertTrue($card['verified'], 'A card without a flag must stay visible');
        }
    }

    public function test_rows_name_the_section_type(): void
    {
        $this->actingAs($this->manager())->get('/admin/pages/investor-guide')->assertOk()
            ->assertSee(__('Guide chapter'))->assertSee(__('Checklist'));
    }

    public function test_a_duplicated_section_gets_its_own_id(): void
    {
        $component = Livewire::actingAs($this->manager())->test(EditHomePage::class);
        $sections = $component->get('data.sections');
        $first = $sections[array_key_first($sections)];
        $sections['copy'] = $first;
        $component->set('data.sections', $sections)->call('save')->assertHasNoErrors();
        $ids = array_column($this->home()->draft_sections, 'id');
        $this->assertSame(count($ids), count(array_unique($ids)));
    }

    public function test_newsroom_page_menu_stories_and_preview(): void
    {
        $user = $this->manager();
        // Menu: stories open beneath Pages › Newsroom and appear nowhere else
        $this->actingAs($user)->get('/admin/pages/news')->assertOk()->assertSee('/admin/news-articles/create', false);
        $this->actingAs($user)->get('/admin')->assertDontSee('/admin/news-articles/create"', false);
        $story = NewsArticle::query()->orderByDesc('published_on')->first();
        $this->actingAs($user)->get("/admin/news-articles/{$story->id}/edit")->assertOk()->assertSee('/admin/pages/news', false);

        // The overview page is edited like the others
        $component = Livewire::actingAs($user)->test(EditNewsPage::class);
        $sections = $component->get('data.sections');
        $key = collect($sections)->search(fn ($s) => $s['type'] === 'newsGrid');
        $sections[$key]['title']['ar'] = 'عنوان جديد للأخبار';
        $component->set('data.sections', $sections)->call('save')->assertHasNoErrors()->callAction('publish');
        $this->get('/ar/news')->assertInertia(fn (Assert $p) => $p->where('sections', fn ($s) => collect($s)->contains('title', 'عنوان جديد للأخبار')));

        // A draft can be previewed by staff only, and stays off the site
        $story->update(['status' => 'draft']);
        $this->get("/en/news/{$story->slug}")->assertNotFound();
        $this->actingAs($user)->get("/preview/news/{$story->slug}/ar")->assertOk()->assertInertia(fn (Assert $p) => $p->component('NewsArticlePage')->where('preview', true));
        auth()->logout();
        $this->get("/preview/news/{$story->slug}/ar")->assertRedirect();
    }

    public function test_a_scheduled_story_goes_live_by_itself(): void
    {
        $story = NewsArticle::query()->first();
        $slug = $story->slug;
        $title = $story->translation('en')->title;
        $story->update(['status' => 'published', 'publish_at' => now()->addWeek()]);

        // Before the time: nowhere on the site
        $this->get("/en/news/{$slug}")->assertNotFound();
        $this->get('/en/news')->assertInertia(fn (Assert $p) => $p->where('articles', fn ($a) => ! collect($a)->contains('slug', $slug)));
        $this->get('/en')->assertInertia(fn (Assert $p) => $p->where('articles', fn ($a) => ! collect($a)->contains('slug', $slug)));
        $this->assertStringNotContainsString("/news/{$slug}<", $this->get('/sitemap.xml')->getContent());
        // Staff can preview it and find it as scheduled
        $this->assertTrue($story->fresh()->isScheduled());
        $manager = $this->manager();
        $this->actingAs($manager)->get("/preview/news/{$slug}/en")->assertOk();
        Livewire::actingAs($manager)->test(ListNewsArticles::class)->assertSee(__('Scheduled to publish'));
        auth()->logout();

        // A week later it is live with nobody touching it, and leads the list
        $this->travel(8)->days();
        $this->get("/en/news/{$slug}")->assertOk();
        $this->get('/en/news')->assertInertia(fn (Assert $p) => $p->where('articles.0.slug', $slug));
        $this->assertSame($title, $story->fresh()->translation('en')->title);
    }

    public function test_publishers_are_told_once_when_a_scheduled_story_goes_live(): void
    {
        Mail::fake();
        $publisher = $this->manager();
        $publisher->forceFill(['admin_locale' => 'en'])->save();
        $editor = User::factory()->withRole(Role::Editor)->create();
        $announce = app(AnnouncePublishedStories::class);
        $story = NewsArticle::query()->first();
        $story->update(['status' => 'published', 'publish_at' => now()->addHour()]);

        // Before its time, and for stories published straight away: nothing
        $this->assertSame(0, $announce());
        Mail::assertNothingSent();

        $this->travel(2)->hours();
        $this->assertSame(1, $announce());
        Mail::assertSent(ScheduledStoryPublished::class, 1);
        Mail::assertSent(ScheduledStoryPublished::class, fn ($m) => $m->hasTo($publisher->email) && str_contains($m->render(), "/en/news/{$story->slug}"));
        $this->assertSame(1, $publisher->notifications()->count());
        $this->assertSame(0, $editor->notifications()->count());

        // Never twice, and a new time announces again
        $this->assertSame(0, $announce());
        $story->refresh()->update(['publish_at' => now()->addHour()]);
        $this->assertNull($story->fresh()->live_notified_at);
        $this->travel(2)->hours();
        $this->assertSame(1, $announce());

        // A story taken back to draft before its time is not announced
        $story->refresh()->update(['status' => 'draft', 'publish_at' => now()->addHour()]);
        $this->travel(2)->hours();
        $this->assertSame(0, $announce());
    }

    public function test_a_mail_failure_keeps_the_story_live_and_the_bell(): void
    {
        $publisher = $this->manager();
        $story = NewsArticle::query()->first();
        $story->update(['status' => 'published', 'publish_at' => now()->subMinute()]);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));
        $this->assertSame(1, app(AnnouncePublishedStories::class)());
        $this->assertSame(1, $publisher->notifications()->count());
        $this->get("/en/news/{$story->slug}")->assertOk();
    }

    public function test_the_command_is_scheduled_every_minute(): void
    {
        $this->artisan('cm:announce-stories')->assertSuccessful();
        $events = collect(app(Schedule::class)->events());
        $this->assertTrue($events->contains(fn ($e) => str_contains((string) $e->command, 'cm:announce-stories') && $e->expression === '* * * * *'));
    }

    public function test_the_schedule_action_sets_status_and_time(): void
    {
        $story = NewsArticle::query()->first();
        $story->update(['status' => 'draft']);
        $at = now()->addDays(3)->startOfMinute();
        Livewire::actingAs($this->manager())->test(ListNewsArticles::class)
            ->callTableAction('schedule', $story, ['publish_at' => $at->copy()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s')])
            ->assertHasNoTableActionErrors();
        $story->refresh();
        $this->assertSame('published', $story->status);
        $this->assertTrue($story->publish_at->equalTo($at), 'Stored in UTC from a Riyadh-time entry');

        // Editors cannot schedule: only publishers may make a story live
        Livewire::actingAs(User::factory()->withRole(Role::Editor)->create())
            ->test(ListNewsArticles::class)->assertTableActionHidden('schedule', $story);
    }

    public function test_story_google_texts_and_fixed_wording_are_editable(): void
    {
        $story = NewsArticle::query()->live()->first();
        $story->translation('ar')->update(['seo_title' => 'عنوان جوجل للخبر', 'seo_description' => 'وصف جوجل للخبر']);
        $this->get("/ar/news/{$story->slug}")->assertInertia(fn (Assert $p) => $p->where('seo.title', 'عنوان جوجل للخبر')->where('seo.description', 'وصف جوجل للخبر'));
        $this->get("/en/news/{$story->slug}")->assertInertia(fn (Assert $p) => $p->where('seo.title', fn ($t) => str_ends_with($t, '| Creative Mark')));

        $component = Livewire::actingAs($this->manager())->test(EditNewsLabels::class);
        $this->assertSame('أخبار ذات صلة', $component->get('data.labels.relatedNews.ar'));
        $component->set('data.labels.relatedNews.ar', 'اقرأ أيضًا')->call('save')->assertHasNoErrors();
        $this->get("/ar/news/{$story->slug}")->assertInertia(fn (Assert $p) => $p->where('labels.relatedNews', 'اقرأ أيضًا')->missing('labels.source'));
        $this->get("/en/news/{$story->slug}")->assertInertia(fn (Assert $p) => $p->missing('labels.relatedNews'));
    }

    public function test_case_studies_page_menu_form_and_preview(): void
    {
        $user = $this->manager();
        $case = CaseStudy::query()->with('translations')->orderBy('sort_order')->first();
        $this->actingAs($user)->get('/admin/pages/case-studies')->assertOk()->assertSee('/admin/case-studies/create', false)->assertSee('case-texts', false);
        $this->actingAs($user)->get('/admin')->assertDontSee('/admin/case-studies/create"', false);
        $this->actingAs($user)->get("/admin/case-studies/{$case->id}/edit")->assertOk();

        // Steps are edited as pairs and stored as the two lists the page reads
        $component = Livewire::actingAs($user)->test(EditCaseStudy::class, ['record' => $case->getRouteKey()]);
        $steps = $component->get('data.translations.en.steps');
        $this->assertCount(count($case->translation('en')->step_titles), $steps);
        $component->set('data.translations.en.steps', [['title' => 'First', 'text' => 'One'], ['title' => 'Second', 'text' => 'Two']])
            ->set('data.translations.en.seo_title', 'Case title for Google')->call('save')->assertHasNoErrors();
        $this->get("/en/case-studies/{$case->slug}")->assertInertia(fn (Assert $p) => $p->component('CaseStudyPage')
            ->where('seo.title', 'Case title for Google')->where('caseStudy.steps.1.title', 'Second')->where('caseStudy.steps.1.text', 'Two'));

        // A real client needs recorded consent
        Livewire::actingAs($user)->test(EditCaseStudy::class, ['record' => $case->getRouteKey()])
            ->set('data.illustrative', false)->set('data.consent_recorded_at', null)->call('save')->assertHasErrors(['data.consent_recorded_at']);

        // Fixed wording
        Livewire::actingAs($user)->test(EditCaseLabels::class)->set('data.labels.moreCases.ar', 'قصص أخرى')->call('save')->assertHasNoErrors();
        $this->get("/ar/case-studies/{$case->slug}")->assertInertia(fn (Assert $p) => $p->where('labels.moreCases', 'قصص أخرى'));

        // Draft preview for staff only
        $case->update(['status' => 'draft']);
        $this->get("/en/case-studies/{$case->slug}")->assertNotFound();
        $this->actingAs($user)->get("/preview/case-studies/{$case->slug}/ar")->assertOk()->assertInertia(fn (Assert $p) => $p->where('preview', true));
        auth()->logout();
        $this->get("/preview/case-studies/{$case->slug}/ar")->assertRedirect();
    }

    public function test_legal_pages_keep_their_long_text_and_can_be_changed(): void
    {
        $component = Livewire::actingAs($this->manager())->test(EditPrivacyPage::class);
        $sections = $component->get('data.sections');
        $key = collect($sections)->search(fn ($s) => $s['type'] === 'legalText');
        $sections[$key]['body']['ar'] = "## عنوان جديد\nفقرة جديدة";
        $component->set('data.sections', $sections)->call('save')->assertHasNoErrors()->callAction('publish');
        $this->get('/ar/privacy')->assertInertia(fn (Assert $p) => $p->where('sections.1.body', "## عنوان جديد\nفقرة جديدة"));
    }

    public function test_booking_and_error_wording_is_editable_and_pages_do_not_overwrite_each_other(): void
    {
        $user = $this->manager();
        Livewire::actingAs($user)->test(EditBookingTexts::class)->set('data.labels.confirmBooking.ar', 'أكّد موعدي')->call('save')->assertHasNoErrors();
        Livewire::actingAs($user)->test(EditErrorTexts::class)->set('data.labels.notFoundTitle.ar', 'الصفحة غير موجودة')->call('save')->assertHasNoErrors();
        auth()->logout();

        $this->get('/ar/consultation')->assertInertia(fn (Assert $p) => $p->where('ui.confirmBooking', 'أكّد موعدي')->where('ui.notFoundTitle', 'الصفحة غير موجودة'));
        $this->get('/ar/nothing-here')->assertNotFound()->assertInertia(fn (Assert $p) => $p->where('ui.notFoundTitle', 'الصفحة غير موجودة'));
        $this->get('/en/consultation')->assertInertia(fn (Assert $p) => $p->where('ui.confirmBooking', fn ($t) => $t !== 'أكّد موعدي'));

        // Back to the standard wording: nothing stored for it
        Livewire::actingAs($user)->test(EditBookingTexts::class)->set('data.labels.confirmBooking.ar', UiStrings::standard('ar')['confirmBooking'])->call('save');
        $this->assertArrayNotHasKey('confirmBooking', Setting::group('ui')['labels'] ?? []);
    }

    public function test_menu_button_offices_and_footer_are_editable(): void
    {
        $admin = User::factory()->withRole(Role::SuperAdmin)->create();
        $this->actingAs($admin)->get('/admin/settings/navigation')->assertOk();
        $component = Livewire::actingAs($admin)->test(EditNavigationSettings::class);
        $header = $component->get('data.header');
        $first = array_key_first($header);
        $header[$first]['label']['ar'] = 'عن الشركة';
        $offices = $component->get('data.offices');
        $offices[array_key_first($offices)]['address']['ar'] = 'عنوان جديد';
        $component->set('data.header', $header)->set('data.offices', $offices)->set('data.cta.label.ar', 'تحدث إلينا')->set('data.footer.tagline.ar', 'شعار جديد')
            ->call('save')->assertHasNoErrors();

        auth()->logout();
        $this->get('/ar')->assertInertia(fn (Assert $p) => $p->where('site.nav.0.label', 'عن الشركة')->where('site.cta.label', 'تحدث إلينا')
            ->where('site.offices.0.address', 'عنوان جديد')->where('ui.tagline', 'شعار جديد')
            ->where('site.nav', fn ($n) => collect($n)->contains('mega', 'services')));

        // Unsafe links are refused, and other roles cannot open it
        $header[$first]['href'] = 'javascript:alert(1)';
        Livewire::actingAs($admin)->test(EditNavigationSettings::class)->set('data.header', $header)->call('save')->assertHasErrors();
        $this->actingAs(User::factory()->withRole(Role::Editor)->create())->get('/admin/settings/navigation')->assertForbidden();
    }

    public function test_key_figures_need_matching_labels(): void
    {
        $user = $this->manager();
        $story = NewsArticle::query()->first();
        Livewire::actingAs($user)->test(EditNewsArticle::class, ['record' => $story->getRouteKey()])
            ->set('data.translations.en.fact_values', ['1', '2'])->set('data.translations.en.fact_labels', ['only one'])
            ->call('save')->assertHasErrors();
    }

    public function test_saving_a_page_unchanged_loses_nothing(): void
    {
        $normalize = function (array $v) use (&$normalize) {
            $out = [];
            foreach ($v as $k => $x) {
                if ($x === [] || $x === null || $x === '' || ($k === 'hidden' && $x === false)) {
                    continue;
                }
                $out[$k] = is_array($x) ? $normalize($x) : $x;
            }
            ksort($out);

            return $out;
        };
        $user = $this->manager();
        foreach ([[EditHomePage::class, [], 'home'], [EditAboutPage::class, [], 'about'], [EditOurEdgePage::class, [], 'our-edge'],
            [EditServicesPage::class, [], 'services'], [EditServicePage::class, ['service' => 'company-formation'], 'service-company-formation']] as [$class, $params, $key]) {
            $before = Page::where('key', $key)->first()->draft_sections;
            Livewire::actingAs($user)->test($class, $params)->call('save')->assertHasNoErrors();
            $this->assertEquals($normalize($before), $normalize(Page::where('key', $key)->first()->draft_sections), $key);
        }
    }

    public function test_concurrent_edits_do_not_overwrite_each_other(): void
    {
        $first = Livewire::actingAs($this->manager())->test(EditHomePage::class);
        $second = Livewire::actingAs($this->manager())->test(EditHomePage::class);
        $second->call('save');
        $version = $this->home()->lock_version;
        $first->call('save');
        $this->assertSame($version, $this->home()->lock_version);
    }
}
