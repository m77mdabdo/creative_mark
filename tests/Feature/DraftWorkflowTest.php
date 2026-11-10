<?php

namespace Tests\Feature;

use App\Drafts\Draft;
use App\Drafts\Drafts;
use App\Enums\Role;
use App\Filament\Pages\EditBookingTexts;
use App\Filament\Pages\EditNavigationSettings;
use App\Filament\Resources\Faqs\Pages\EditFaq;
use App\Filament\Resources\Regions\Pages\EditRegion;
use App\Filament\Resources\Stats\Pages\EditStat;
use App\Models\Faq;
use App\Models\Region;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Stat;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

/** Save keeps changes to live content as a draft; Preview shows them to staff only; Publish makes them live. */
class DraftWorkflowTest extends TestCase
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

    public function test_a_live_figure_changes_only_when_published(): void
    {
        $stat = Stat::query()->live()->orderBy('sort_order')->first();
        $old = $stat->value;
        $manager = $this->manager();

        $page = Livewire::actingAs($manager)->test(EditStat::class, ['record' => $stat->getRouteKey()])
            ->fillForm(['value' => '7,777+'])->call('save')->assertHasNoFormErrors()
            ->assertNotified(__('Draft saved. Publish to show it on the website.'));
        $this->assertSame($old, $stat->fresh()->value);
        $this->get('/en')->assertInertia(fn (Assert $p) => $p->where('stats', fn ($s) => ! collect($s)->contains('value', '7,777+')));

        // The dashboard shows the draft, not the live value
        Livewire::actingAs($manager)->test(EditStat::class, ['record' => $stat->getRouteKey()])->assertSchemaStateSet(['value' => '7,777+']);

        // Preview: staff see it, the database does not change, visitors do not see it
        $this->actingAs($manager)->get('/preview?path=/&locale=en');
        $this->get('/en')->assertInertia(fn (Assert $p) => $p->where('preview', true)->where('stats', fn ($s) => collect($s)->contains('value', '7,777+')));
        $this->assertSame($old, $stat->fresh()->value);
        auth()->logout();
        $this->get('/en')->assertInertia(fn (Assert $p) => $p->where('preview', false)->where('stats', fn ($s) => ! collect($s)->contains('value', '7,777+')));

        $this->actingAs($manager);
        $page->callAction('publish')->assertHasNoErrors();
        $this->assertSame('7,777+', $stat->fresh()->value);
        $this->assertFalse(app(Drafts::class)->has(Drafts::recordKey($stat)));
    }

    public function test_changes_can_be_discarded(): void
    {
        $stat = Stat::query()->live()->first();
        $old = $stat->value;
        $page = Livewire::actingAs($this->manager())->test(EditStat::class, ['record' => $stat->getRouteKey()])
            ->fillForm(['value' => '1'])->call('save')
            ->callAction('discard')->assertSchemaStateSet(['value' => $old]);
        $this->assertSame(0, Draft::count());
        $page->assertActionHidden('discard');
    }

    public function test_hidden_records_are_saved_directly(): void
    {
        $faq = Faq::query()->first();
        $faq->update(['status' => 'draft']);
        Livewire::actingAs($this->manager())->test(EditFaq::class, ['record' => $faq->getRouteKey()])
            ->fillForm(['sort_order' => 42])->call('save')->assertHasNoFormErrors()->assertNotified(__('Saved'));
        $this->assertSame(42, (int) $faq->fresh()->sort_order);
        $this->assertSame(0, Draft::count());
    }

    public function test_lists_inside_a_form_wait_with_the_draft(): void
    {
        $region = Region::query()->live()->first();
        $before = $region->services()->pluck('services.id')->sort()->values()->all();
        $other = Service::query()->whereNotIn('id', $before)->value('id');
        $page = Livewire::actingAs($this->manager())->test(EditRegion::class, ['record' => $region->getRouteKey()])
            ->fillForm(['services' => [$other]])->call('save')->assertHasNoFormErrors();
        $this->assertSame($before, $region->services()->pluck('services.id')->sort()->values()->all());
        $page->callAction('publish');
        $this->assertSame([$other], $region->services()->pluck('services.id')->all());
    }

    public function test_editors_draft_changes_and_publishers_publish_them(): void
    {
        $stat = Stat::query()->live()->first();
        $old = $stat->value;
        Livewire::actingAs(User::factory()->withRole(Role::Editor)->create())->test(EditStat::class, ['record' => $stat->getRouteKey()])
            ->assertActionHidden('publish')->assertActionVisible('preview')
            ->fillForm(['value' => '5,000+'])->call('save')->assertHasNoFormErrors();
        $this->assertSame($old, $stat->fresh()->value);

        Livewire::actingAs($this->manager())->test(EditStat::class, ['record' => $stat->getRouteKey()])
            ->assertSchemaStateSet(['value' => '5,000+'])->callAction('publish');
        $this->assertSame('5,000+', $stat->fresh()->value);
    }

    public function test_two_wording_screens_never_overwrite_each_other(): void
    {
        $admin = User::factory()->withRole(Role::SuperAdmin)->create();
        Livewire::actingAs($admin)->test(EditBookingTexts::class)->set('data.labels.confirmBooking.ar', 'أكّد')->call('save');
        Livewire::actingAs($admin)->test(EditNavigationSettings::class)->set('data.footer.tagline.ar', 'شعار')->call('save');
        $this->assertArrayNotHasKey('confirmBooking', Setting::group('ui')['labels'] ?? []);

        Livewire::actingAs($admin)->test(EditNavigationSettings::class)->callAction('publish');
        Livewire::actingAs($admin)->test(EditBookingTexts::class)->callAction('publish');
        $labels = Setting::group('ui')['labels'];
        $this->assertSame('أكّد', $labels['confirmBooking']['ar']);
        $this->assertSame('شعار', $labels['tagline']['ar']);
    }

    public function test_pages_send_browser_protection_headers(): void
    {
        foreach (['/en', '/admin/login'] as $url) {
            $this->get($url)->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
    }

    public function test_preview_mode_needs_an_editor_and_expires(): void
    {
        $agent = User::factory()->withRole(Role::SalesAgent)->create();
        $this->actingAs($agent)->withSession(['cm_preview_until' => now()->addHour()->getTimestamp()])
            ->get('/en')->assertInertia(fn (Assert $p) => $p->where('preview', false));

        $this->actingAs($this->manager())->withSession(['cm_preview_until' => now()->subMinute()->getTimestamp()])
            ->get('/en')->assertInertia(fn (Assert $p) => $p->where('preview', false));
    }
}
