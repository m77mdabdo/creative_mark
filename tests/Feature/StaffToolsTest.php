<?php

namespace Tests\Feature;

use App\Consultation\BookingExport;
use App\Content\MediaLibrary;
use App\Enums\Role;
use App\Filament\Pages\MediaLibraryPage;
use App\Filament\Resources\ConsultationRequests\Pages\ListConsultationRequests;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Http\Middleware\EnsurePrivilegedRolesUseMfa;
use App\Mail\StaffAccessLink;
use App\Models\ActivityLog;
use App\Models\ConsultationRequest;
use App\Models\Faq;
use App\Models\MediaAsset;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Database\Seeders\RbacSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

/** Staff management, booking export, media library and the activity log. */
class StaffToolsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RbacSeeder::class, ContentSeeder::class]);
        $this->withoutMiddleware(EnsurePrivilegedRolesUseMfa::class);
        MediaLibrary::flush();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function admin(): User
    {
        return User::factory()->withRole(Role::SuperAdmin)->create();
    }

    public function test_new_staff_are_invited_by_email_and_choose_their_own_password(): void
    {
        Mail::fake();
        $admin = $this->admin();
        Livewire::actingAs($admin)->test(CreateUser::class)
            ->fillForm(['name' => 'Nora', 'email' => 'nora@example.org', 'role' => 'editor', 'admin_locale' => 'en', 'is_active' => true])
            ->call('create')->assertHasNoFormErrors();

        $nora = User::where('email', 'nora@example.org')->first();
        $this->assertTrue($nora->hasRole('editor'));
        $this->assertTrue($nora->is_active);
        Mail::assertSent(StaffAccessLink::class, fn ($m) => $m->hasTo('nora@example.org'));
        $this->assertTrue(ActivityLog::where('event', 'invited')->where('subject_id', $nora->id)->exists());
    }

    public function test_only_super_admins_manage_staff(): void
    {
        foreach ([Role::ContentManager, Role::SalesManager, Role::Editor] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create())->get('/admin/users')->assertForbidden();
        }
        $this->actingAs($this->admin())->get('/admin/users')->assertOk();
    }

    public function test_role_and_status_can_change_but_nobody_can_lock_the_site_out(): void
    {
        $admin = $this->admin();
        $agent = User::factory()->withRole(Role::SalesAgent)->create();

        Livewire::actingAs($admin)->test(EditUser::class, ['record' => $agent->getRouteKey()])
            ->fillForm(['role' => 'sales_manager', 'is_active' => false])->call('save')->assertHasNoFormErrors();
        $agent->refresh();
        $this->assertTrue($agent->hasRole('sales_manager'));
        $this->assertFalse($agent->is_active);

        // Own account: role and status are protected even if the form is tampered with
        Livewire::actingAs($admin)->test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->fillForm(['name' => 'Renamed', 'role' => 'viewer', 'is_active' => false])->call('save');
        $admin->refresh();
        $this->assertSame('Renamed', $admin->name);
        $this->assertTrue($admin->hasRole('super_admin'));
        $this->assertTrue($admin->is_active);

        // Delete is offered for others, never for yourself
        Livewire::actingAs($admin)->test(ListUsers::class)
            ->assertTableActionHidden('delete', $admin)->assertTableActionVisible('delete', $agent);
        Livewire::actingAs($admin)->test(ListUsers::class)->callTableAction('delete', $agent);
        $this->assertNull(User::find($agent->id));
    }

    public function test_password_link_and_two_step_reset(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $staff = User::factory()->withRole(Role::Editor)->create();
        $staff->forceFill(['app_authentication_secret' => 'SECRET'])->save();

        $list = Livewire::actingAs($admin)->test(ListUsers::class);
        $list->callTableAction('sendLink', $staff);
        Mail::assertSent(StaffAccessLink::class, fn ($m) => $m->hasTo($staff->email) && ! $m->invitation);
        $list->callTableAction('resetMfa', $staff);
        $this->assertNull($staff->fresh()->app_authentication_secret);
        $this->assertTrue(ActivityLog::where('event', 'mfa_reset')->where('subject_id', $staff->id)->exists());
    }

    public function test_the_activity_log_records_staff_changes_without_values(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $faq = Faq::query()->first();
        $faq->update(['sort_order' => 99]);
        $log = ActivityLog::where('subject_type', 'Faq')->where('subject_id', $faq->id)->latest('id')->first();
        $this->assertSame('updated', $log->event);
        $this->assertSame($admin->name, $log->user_name);
        $this->assertSame(['sort_order'], $log->fields);
        $this->assertStringNotContainsString('99', json_encode($log->toArray()['fields']));

        // A translation counts as a change to its record, one line per save
        $count = ActivityLog::count();
        foreach ($faq->translations()->get() as $row) {
            $row->fill(['answer' => "new {$row->locale}"])->save();
        }
        $this->assertSame($count, ActivityLog::count());

        // Password changes are noted, never stored
        $admin->update(['password' => 'Another-Secret-123456']);
        $line = ActivityLog::where('subject_type', 'User')->latest('id')->first();
        $this->assertStringNotContainsString('Another-Secret', json_encode($line->fields));

        // Visitors and seeders are not staff
        auth()->logout();
        $before = ActivityLog::count();
        Faq::query()->first()->update(['sort_order' => 5]);
        $this->assertSame($before, ActivityLog::count());
    }

    public function test_activity_log_is_read_only_and_limited(): void
    {
        $this->actingAs(User::factory()->withRole(Role::ContentManager)->create())->get('/admin/activity-logs')->assertOk();
        $this->actingAs(User::factory()->withRole(Role::Editor)->create())->get('/admin/activity-logs')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/activity-logs/create')->assertNotFound();

        $old = ActivityLog::create(['event' => 'updated', 'created_at' => now()->subYears(2)]);
        $this->artisan('model:prune', ['--model' => [ActivityLog::class]]);
        $this->assertNull(ActivityLog::find($old->id));
    }

    public function test_sign_in_is_recorded(): void
    {
        $user = User::factory()->withRole(Role::Editor)->create(['password' => 'Long-Password-123456']);
        $this->assertTrue(auth()->attempt(['email' => $user->email, 'password' => 'Long-Password-123456']));
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertTrue(ActivityLog::where('event', 'login')->where('user_id', $user->id)->exists());
        auth()->logout();
        $this->assertFalse(auth()->attempt(['email' => $user->email, 'password' => 'wrong']));
        $this->assertTrue(ActivityLog::where('event', 'login_failed')->where('subject_label', $user->email)->exists());
    }

    private function booking(array $overrides = []): ConsultationRequest
    {
        return ConsultationRequest::create(['reference' => 'CM-'.fake()->unique()->numerify('######-####'), 'name' => 'Sara', 'email' => 'c@example.org', 'phone' => '+966500000000',
            'meeting_mode' => 'video', 'preferred_date' => now()->addDays(2)->toDateString(), 'preferred_slot' => '10:00', 'locale' => 'ar', 'consent_at' => now(), 'country' => 'eg', 'service' => 'company-formation', ...$overrides]);
    }

    public function test_bookings_export_to_a_valid_excel_file_safely(): void
    {
        $this->booking(['name' => 'سارة <b>', 'message' => '=HYPERLINK("http://evil.test","x")']);
        $this->actingAs($this->admin());
        app()->setLocale('ar');
        [$path, $count] = BookingExport::toFile(ConsultationRequest::query());
        $this->assertSame(1, $count);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotFalse(simplexml_load_string($sheet), 'Valid XML');
        $this->assertStringContainsString('rightToLeft="1"', $sheet);
        $this->assertStringContainsString('سارة &lt;b&gt;', $sheet);
        $this->assertStringContainsString('t="inlineStr"', $sheet);
        $this->assertStringNotContainsString('<f>', $sheet); // text only: nothing can run as a formula
        $this->assertStringContainsString('تأسيس الشركات', $sheet);
        $zip->close();
        unlink($path);
    }

    public function test_export_needs_permission_and_is_logged(): void
    {
        $this->booking();
        $manager = User::factory()->withRole(Role::SalesManager)->create();
        Livewire::actingAs($manager)->test(ListConsultationRequests::class)->callAction('export', ['from' => null, 'until' => null, 'statuses' => []])->assertFileDownloaded();
        $this->assertTrue(ActivityLog::where('event', 'exported')->where('user_id', $manager->id)->exists());
        Livewire::actingAs(User::factory()->withRole(Role::SalesAgent)->create())->test(ListConsultationRequests::class)->assertActionHidden('export');
    }

    public function test_media_library_lists_usage_edits_alt_and_deletes_only_unused_uploads(): void
    {
        $used = 'hero-riyadh-kingdom-dusk';
        $this->assertNotEmpty(MediaLibrary::usage()[$used] ?? []);
        $this->assertFalse(MediaLibrary::canDelete($used));

        $file = tempnam(sys_get_temp_dir(), 'img');
        imagepng(imagecreatetruecolor(900, 600), $file);
        $key = MediaLibrary::store($file, 'Office', 'مكتب');
        $this->assertFileExists(public_path("media/uploads/{$key}-800.webp"));
        $this->assertTrue(MediaLibrary::canDelete($key));

        $manager = User::factory()->withRole(Role::ContentManager)->create();
        $this->actingAs($manager)->get('/admin/media-library')->assertOk()->assertSee('مكتب');
        $page = Livewire::actingAs($manager)->test(MediaLibraryPage::class);
        $page->callAction('edit', ['alt' => ['en' => 'Office door', 'ar' => 'باب المكتب']], ['key' => $key])->assertHasNoActionErrors();
        MediaLibrary::flush();
        $this->assertSame('Office door', MediaLibrary::all()[$key]['alt']['en']);
        $this->assertTrue(ActivityLog::where('subject_type', 'Media')->where('event', 'updated')->exists());
        $this->assertFalse(ActivityLog::where('subject_type', 'Setting')->exists());

        // In use: refused even if requested directly
        $service = Service::query()->first();
        $service->update(['media' => $key]);
        MediaLibrary::flush();
        $page->callAction('delete', [], ['key' => $key]);
        MediaLibrary::flush();
        $this->assertArrayHasKey($key, MediaLibrary::all());

        // Unused: gone, files too
        $service->update(['media' => $used]);
        Livewire::actingAs($manager)->test(MediaLibraryPage::class)->callAction('delete', [], ['key' => $key]);
        MediaLibrary::flush();
        $this->assertArrayNotHasKey($key, MediaLibrary::all());
        $this->assertFileDoesNotExist(public_path("media/uploads/{$key}-800.webp"));

        // Editors upload but cannot delete; sales staff cannot open it
        $this->actingAs(User::factory()->withRole(Role::SalesAgent)->create())->get('/admin/media-library')->assertForbidden();
        // The shipped photography is untouched
        $this->assertSame(129, MediaAsset::count());
    }

    public function test_simultaneous_uploads_do_not_lose_each_other(): void
    {
        $files = [];
        foreach ([1, 2] as $i) {
            $files[$i] = tempnam(sys_get_temp_dir(), 'img');
            imagepng(imagecreatetruecolor(900, 600), $files[$i]);
        }
        $first = MediaLibrary::store($files[1], 'First');
        // A second request that read the library before the first upload finished
        MediaLibrary::flush();
        $second = MediaLibrary::store($files[2], 'Second');
        MediaLibrary::flush();
        $this->assertArrayHasKey($first, MediaLibrary::all());
        $this->assertArrayHasKey($second, MediaLibrary::all());
        foreach ([$first, $second] as $key) {
            array_map('unlink', glob(public_path("media/uploads/{$key}-*.webp")));
        }
    }
}
