<?php

namespace Tests\Feature;

use App\Consultation\Calendar;
use App\Consultation\Notifier;
use App\Content\MediaLibrary;
use App\Content\SectionSchema;
use App\Drafts\PreviewMode;
use App\Enums\ConsultationStatus;
use App\Enums\Role;
use App\Filament\Pages\MediaLibraryPage;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Support\SectionForm;
use App\Models\ConsultationRequest;
use App\Models\NotificationRecipient;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Database\Seeders\RbacSeeder;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/** Upload paths, booking caps, deactivated accounts, media permissions and smaller hardening. */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $cleanup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RbacSeeder::class, ContentSeeder::class]);
        MediaLibrary::flush();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanup as $path) {
            @unlink($path);
        }
        parent::tearDown();
    }

    /** A file outside the upload folder that a tampered upload must never touch. */
    private function victim(string $relative): string
    {
        $path = storage_path($relative);
        file_put_contents($path, 'keep me');
        $this->cleanup[] = $path;

        return $path;
    }

    private function png(int $w = 900, int $h = 600): string
    {
        $dir = storage_path('app/private/'.MediaLibrary::UPLOAD_DIR);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = $dir.'/test-'.bin2hex(random_bytes(4)).'.png';
        imagepng(imagecreatetruecolor($w, $h), $path);
        $this->cleanup[] = $path;

        return $path;
    }

    // 1. Upload path traversal

    public function test_media_library_upload_with_a_tampered_path_deletes_nothing(): void
    {
        $outside = $this->victim('app/victim-'.bin2hex(random_bytes(3)).'.txt');
        $manager = User::factory()->withRole(Role::ContentManager)->create();

        Livewire::actingAs($manager)->test(MediaLibraryPage::class)
            ->callAction('upload', ['file' => ['tampered' => '../'.basename($outside)], 'alt_en' => 'Anything']);

        $this->assertFileExists($outside);
        $this->assertSame('keep me', file_get_contents($outside));
    }

    public function test_media_library_upload_still_works_for_a_real_file(): void
    {
        $manager = User::factory()->withRole(Role::ContentManager)->create();
        $before = count(MediaLibrary::all());

        Livewire::actingAs($manager)->test(MediaLibraryPage::class)
            ->callAction('upload', ['file' => UploadedFile::fake()->image('office.png', 900, 600), 'alt_en' => 'Office'])
            ->assertHasNoActionErrors();

        MediaLibrary::flush();
        $this->assertCount($before + 1, MediaLibrary::all());
        $key = (string) collect(MediaLibrary::all())->filter(fn ($m) => ($m['folder'] ?? null) === 'uploads')->keys()->last();
        $this->cleanup = [...$this->cleanup, ...(glob(public_path("media/uploads/{$key}-*.webp")) ?: [])];
        $this->assertNotEmpty($this->cleanup);
        $this->assertSame([], glob(storage_path('app/private/'.MediaLibrary::UPLOAD_DIR.'/*')) ?: []);
    }

    public function test_upload_paths_must_stay_inside_the_upload_folder(): void
    {
        $good = $this->png();
        $this->victim('app/private/outside.png');
        $link = storage_path('app/private/'.MediaLibrary::UPLOAD_DIR.'/link.png');
        @unlink($link);
        symlink(storage_path('app/private/outside.png'), $link);
        $this->cleanup[] = $link;

        $this->assertSame(realpath($good), MediaLibrary::resolveUpload(MediaLibrary::UPLOAD_DIR.'/'.basename($good)));
        foreach (['../../../.env', '/etc/passwd', 'outside.png', MediaLibrary::UPLOAD_DIR.'/../outside.png',
            MediaLibrary::UPLOAD_DIR.'/./'.basename($good), MediaLibrary::UPLOAD_DIR.'\\..\\outside.png',
            MediaLibrary::UPLOAD_DIR.'/link.png', MediaLibrary::UPLOAD_DIR.'/missing.png', '', null, ['x']] as $bad) {
            $this->assertNull(MediaLibrary::resolveUpload($bad), json_encode($bad));
        }

        $outside = $this->victim('app/victim-'.bin2hex(random_bytes(3)).'.txt');
        try {
            MediaLibrary::storeUpload('../'.basename($outside), 'x');
            $this->fail('A path outside the upload folder was accepted.');
        } catch (RuntimeException) {
            $this->assertFileExists($outside);
        }
    }

    public function test_upload_from_an_image_picker_cannot_reach_files_outside_the_folder(): void
    {
        $outside = $this->victim('app/victim-'.bin2hex(random_bytes(3)).'.txt');
        $editor = User::factory()->withRole(Role::Editor)->create();

        Livewire::actingAs($editor)->test(MediaPickerForm::class)
            ->callAction(TestAction::make('createOption')->schemaComponent('media'), ['file' => ['tampered' => '../'.basename($outside)], 'alt_en' => 'x']);

        $this->assertFileExists($outside);
    }

    // 2. Booking slot exhaustion

    /** @return array<string, mixed> */
    private function booking(string $slot, string $email, int $day = 0): array
    {
        $calendar = app(Calendar::class);

        return [
            'service' => 'company-formation', 'region' => 'riyadh', 'stage' => 'ready',
            'name' => 'Sara Ahmed', 'email' => $email, 'phone' => '+966 55 123 4567', 'company' => 'Acme',
            'country' => 'eg', 'meeting_mode' => 'video', 'preferred_date' => $calendar->dates()[$day], 'preferred_slot' => $slot,
            'message' => 'We want to open an office.', 'consent' => true, 'website' => '',
            'form_token' => encrypt((string) (time() - 30)),
        ];
    }

    public function test_one_address_can_book_at_most_three_a_day_even_with_different_emails(): void
    {
        Mail::fake();
        config(['consultation.per_ip_per_hour' => 100]);
        foreach (['10:00', '11:30', '13:00'] as $i => $slot) {
            $this->post('/en/consultation', $this->booking($slot, "visitor{$i}@example.org"))->assertSessionHasNoErrors();
        }
        $this->post('/en/consultation', $this->booking('14:30', 'visitor9@example.org'))->assertSessionHasErrors(['form' => 'too_many']);
        $this->assertSame(3, ConsultationRequest::count());
    }

    public function test_unhandled_bookings_per_day_are_capped_for_everyone(): void
    {
        Mail::fake();
        config(['consultation.new_per_day' => 2]);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])->post('/en/consultation', $this->booking('10:00', 'a@example.org'))->assertSessionHasNoErrors();
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])->post('/en/consultation', $this->booking('11:30', 'b@example.org'))->assertSessionHasNoErrors();
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.3'])->post('/en/consultation', $this->booking('13:00', 'c@example.org'))->assertSessionHasErrors(['form' => 'too_many']);
        $this->assertSame(2, ConsultationRequest::count());

        // Once the team handles one, there is room again
        ConsultationRequest::query()->first()->update(['status' => ConsultationStatus::Contacted]);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.4'])->post('/en/consultation', $this->booking('13:00', 'd@example.org'))->assertSessionHasNoErrors();
    }

    // 3. Deactivated accounts

    public function test_preview_mode_needs_an_active_account(): void
    {
        $active = User::factory()->withRole(Role::ContentManager)->create();
        $inactive = User::factory()->withRole(Role::ContentManager)->create();
        $inactive->forceFill(['is_active' => false])->save();

        $this->assertTrue(PreviewMode::allowed($active->fresh()));
        $this->assertFalse(PreviewMode::allowed($inactive->fresh()));
        $this->assertFalse(PreviewMode::allowed(null));
    }

    public function test_deactivating_an_account_ends_its_sessions_and_remember_token(): void
    {
        $admin = User::factory()->withRole(Role::SuperAdmin)->create();
        $agent = User::factory()->withRole(Role::SalesAgent)->create();
        $other = User::factory()->withRole(Role::SalesAgent)->create();
        $agent->forceFill(['remember_token' => 'remember-me-token'])->save();
        foreach ([$agent, $other] as $u) {
            DB::table('sessions')->insert(['id' => 'sess-'.$u->id, 'user_id' => $u->id, 'payload' => '', 'last_activity' => time()]);
        }

        Livewire::actingAs($admin)->test(EditUser::class, ['record' => $agent->getRouteKey()])
            ->fillForm(['is_active' => false])->call('save')->assertHasNoFormErrors();

        $agent->refresh();
        $this->assertFalse($agent->is_active);
        $this->assertNull($agent->remember_token);
        $this->assertFalse(DB::table('sessions')->where('user_id', $agent->id)->exists());
        $this->assertTrue(DB::table('sessions')->where('user_id', $other->id)->exists());

        // Saving an active account leaves its sessions alone
        $other->forceFill(['name' => 'Renamed'])->save();
        $this->assertTrue(DB::table('sessions')->where('user_id', $other->id)->exists());
    }

    // 4. Media permissions

    public function test_image_picker_offers_uploads_only_with_the_upload_right(): void
    {
        Livewire::actingAs(User::factory()->withRole(Role::SeoManager)->create())->test(MediaPickerForm::class)
            ->assertActionHidden(TestAction::make('createOption')->schemaComponent('media'));
        Livewire::actingAs(User::factory()->withRole(Role::Editor)->create())->test(MediaPickerForm::class)
            ->assertActionVisible(TestAction::make('createOption')->schemaComponent('media'));
    }

    public function test_alt_text_edits_need_the_publish_right(): void
    {
        $key = MediaLibrary::all() ? array_key_first(MediaLibrary::all()) : null;
        $before = MediaLibrary::all()[$key]['alt']['en'];
        $editor = User::factory()->withRole(Role::Editor)->create();

        Livewire::actingAs($editor)->test(MediaLibraryPage::class)
            ->assertActionHidden(TestAction::make('edit')->arguments(['key' => $key]))
            // Calling it directly, as a tampered browser could, opens nothing
            ->call('mountAction', 'edit', ['key' => $key])->assertSet('mountedActions', []);
        MediaLibrary::flush();
        $this->assertSame($before, MediaLibrary::all()[$key]['alt']['en']);

        $manager = User::factory()->withRole(Role::ContentManager)->create();
        Livewire::actingAs($manager)->test(MediaLibraryPage::class)
            ->assertActionVisible(TestAction::make('edit')->arguments(['key' => $key]));
    }

    // 6. Smaller hardening

    public function test_links_cannot_be_protocol_relative(): void
    {
        foreach (['//evil.example', '/\\evil.example', '/\\/evil.example', ' //evil.example'] as $href) {
            $this->assertFalse(SectionSchema::safeHref($href), $href);
        }
        foreach (['/en/services', '/', '#contact', 'https://example.org', 'mailto:a@example.org'] as $href) {
            $this->assertTrue(SectionSchema::safeHref($href), $href);
        }
    }

    public function test_huge_images_are_refused_before_decoding(): void
    {
        // A valid PNG header claiming 10,000 × 10,000 pixels (100 megapixels).
        $ihdr = pack('NNCCCCC', 10000, 10000, 8, 2, 0, 0, 0);
        $png = "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.$ihdr.pack('N', crc32('IHDR'.$ihdr));
        $path = $this->png();
        file_put_contents($path, $png);

        $this->expectExceptionMessage('too many pixels');
        MediaLibrary::store($path, 'Huge');
    }

    public function test_mail_errors_are_logged_without_addresses(): void
    {
        NotificationRecipient::create(['name' => 'Sales', 'email' => 'sales@example.org']);
        $data = collect($this->booking('10:00', 'sara@example.org'))->except(['consent', 'website', 'form_token'])->all();
        $booking = ConsultationRequest::create([...$data, 'reference' => 'CM-000000-TEST', 'locale' => 'en', 'consent_at' => now()]);
        Mail::shouldReceive('to')->andThrow(new RuntimeException('550 mailbox <sales@example.org> unavailable'));
        Log::spy();

        app(Notifier::class)->bookingCreated($booking);

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context) {
            $text = json_encode($context);

            return str_contains($text, 'RuntimeException') && str_contains($text, '550 mailbox') && ! str_contains($text, '@');
        })->once();
        $this->assertStringNotContainsString('@', Notifier::describe(new RuntimeException('to <a.b@example.org>: rejected')));
    }
}

/** A bare form holding one image picker, as the page and record editors use it. */
class MediaPickerForm extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions, InteractsWithSchemas;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([SectionForm::mediaSelect('media', 'Image')])->statePath('data');
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}
