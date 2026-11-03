<?php

namespace Tests\Feature;

use App\Content\MediaLibrary;
use App\Enums\Role;
use App\Filament\Pages\EditHomePage;
use App\Filament\Pages\EditJourney;
use App\Filament\Support\IconPicker;
use App\Http\Middleware\EnsurePrivilegedRolesUseMfa;
use App\Models\Page;
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
