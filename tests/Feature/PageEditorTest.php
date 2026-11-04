<?php

namespace Tests\Feature;

use App\Content\MediaLibrary;
use App\Enums\Role;
use App\Filament\Pages\EditAboutPage;
use App\Filament\Pages\EditContactSettings;
use App\Filament\Pages\EditHomePage;
use App\Filament\Pages\EditJourney;
use App\Filament\Pages\EditOurEdgePage;
use App\Filament\Pages\EditServicePage;
use App\Filament\Pages\EditServicesPage;
use App\Filament\Support\IconPicker;
use App\Http\Middleware\EnsurePrivilegedRolesUseMfa;
use App\Models\Page;
use App\Models\Stat;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
