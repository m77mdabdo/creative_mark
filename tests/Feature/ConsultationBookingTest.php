<?php

namespace Tests\Feature;

use App\Consultation\Calendar;
use App\Consultation\Notifier;
use App\Enums\ConsultationStatus;
use App\Enums\Role;
use App\Http\Middleware\EnsurePrivilegedRolesUseMfa;
use App\Mail\ConsultationReceived;
use App\Mail\ConsultationReplyMail;
use App\Mail\NewConsultationBooked;
use App\Models\ConsultationRequest;
use App\Models\NotificationRecipient;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\ContentSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConsultationBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RbacSeeder::class, ContentSeeder::class]);
        Mail::fake();
        RateLimiter::clear('x');
        NotificationRecipient::create(['name' => 'Sales', 'email' => 'sales@example.org']);
        NotificationRecipient::create(['name' => 'Owner', 'email' => 'owner@example.org']);
        NotificationRecipient::create(['name' => 'Old', 'email' => 'old@example.org', 'active' => false]);
    }

    /** @return array<string, mixed> */
    private function valid(array $overrides = []): array
    {
        $calendar = app(Calendar::class);

        return [
            'service' => 'company-formation', 'region' => 'riyadh', 'stage' => 'ready',
            'name' => 'Sara Ahmed', 'email' => 'Sara@Example.org', 'phone' => '+966 55 123 4567', 'company' => 'Acme',
            'country' => 'eg', 'meeting_mode' => 'video', 'preferred_date' => $calendar->dates()[0], 'preferred_slot' => '10:00',
            'message' => 'We want to open an office.', 'consent' => true, 'website' => '',
            'form_token' => encrypt((string) (time() - 30)),
            ...$overrides,
        ];
    }

    public function test_form_lists_working_days_slots_and_prefill(): void
    {
        $this->get('/en/consultation?service=feasibility-studies&region=bogus')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('ConsultationPage')
            ->has('dates', 15)
            ->where('dates', fn ($dates) => collect($dates)->every(fn ($d) => in_array(Carbon::parse($d)->dayOfWeek, [0, 1, 2, 3, 4], true)))
            ->where('slots', ['10:00', '11:30', '13:00', '14:30', '16:00'])
            ->where('prefill.service', 'feasibility-studies')
            ->where('prefill.region', null)
            ->has('formToken'));
    }

    public function test_booking_is_stored_and_everyone_is_notified(): void
    {
        $this->post('/ar/consultation', $this->valid())->assertRedirect('/ar/consultation/thank-you')->assertSessionHas('consultation_ref');

        $booking = ConsultationRequest::sole();
        $this->assertMatchesRegularExpression('/^CM-\d{6}-[2-9A-HJ-NP-Z]{4}$/', $booking->reference);
        $this->assertSame('sara@example.org', $booking->email);
        $this->assertSame('ar', $booking->locale);
        $this->assertSame(ConsultationStatus::New, $booking->status);
        $this->assertNotNull($booking->ip_hash);
        $this->assertNotSame('127.0.0.1', $booking->ip_hash);

        // One separate email per active recipient, the client's confirmation, and nothing to inactive ones.
        Mail::assertSent(NewConsultationBooked::class, 2);
        Mail::assertSent(NewConsultationBooked::class, fn ($m) => $m->hasTo('sales@example.org') && $m->hasReplyTo('sara@example.org'));
        Mail::assertNotSent(NewConsultationBooked::class, fn ($m) => $m->hasTo('old@example.org'));
        Mail::assertSent(ConsultationReceived::class, fn ($m) => $m->hasTo('sara@example.org'));
        $this->assertNotNull($booking->fresh()->notified_at);
        $this->assertNotNull($booking->fresh()->confirmed_at);
        $this->assertNull($booking->fresh()->mail_error);

        $this->get('/ar/consultation/thank-you')->assertInertia(fn (Assert $page) => $page->where('reference', $booking->reference));
    }

    public function test_dashboard_bell_notifies_managers(): void
    {
        $manager = User::factory()->withRole(Role::SalesManager)->create();
        $agent = User::factory()->withRole(Role::SalesAgent)->create();
        $this->post('/en/consultation', $this->valid());
        $this->assertSame(1, $manager->notifications()->count());
        $this->assertSame(0, $agent->notifications()->count());
    }

    public function test_emails_render_in_the_clients_language(): void
    {
        $this->post('/fr/consultation', $this->valid());
        $booking = ConsultationRequest::sole();
        $html = (new ConsultationReceived($booking))->render();
        $this->assertStringContainsString('Votre réservation', $html);
        $this->assertStringContainsString($booking->reference, $html);
        $this->assertStringContainsString('heure de Riyad', $html);

        $staff = (new NewConsultationBooked($booking))->render();
        $this->assertStringContainsString('حجز استشارة', (new NewConsultationBooked($booking))->envelope()->subject);
        $this->assertStringContainsString('sara@example.org', $staff);
    }

    public function test_a_slot_cannot_be_booked_twice(): void
    {
        $this->post('/en/consultation', $this->valid());
        $this->post('/en/consultation', $this->valid(['email' => 'other@example.org']))->assertSessionHasErrors(['preferred_slot' => 'slot_taken']);
        $this->assertSame(1, ConsultationRequest::count());

        // Taken slots are published to the form; a cancelled booking frees its slot.
        $date = app(Calendar::class)->dates()[0];
        $this->get('/en/consultation')->assertInertia(fn (Assert $page) => $page->where("taken.{$date}", ['10:00']));
        ConsultationRequest::query()->update(['status' => ConsultationStatus::Cancelled]);
        $this->post('/en/consultation', $this->valid(['email' => 'other@example.org']))->assertRedirect();
    }

    public function test_invalid_input_is_rejected(): void
    {
        $calendar = app(Calendar::class);
        $cases = [
            ['name' => '<script>alert(1)</script>'],
            ['name' => 'Visit https://spam.example'],
            ['email' => 'not-an-email'],
            ['phone' => 'call me'],
            ['country' => 'zz'],
            ['service' => 'unknown'],
            ['meeting_mode' => 'teleport'],
            ['preferred_date' => now()->toDateString()], // today is not bookable
            ['preferred_date' => now()->addYear()->toDateString()],
            ['preferred_slot' => '03:00'],
            ['message' => 'Hi <b>there</b>'],
            ['message' => 'http://a.x http://b.x http://c.x'],
            ['consent' => false],
            ['stage' => 'nope'],
        ];
        foreach ($cases as $i => $override) {
            $this->post('/en/consultation', $this->valid([...$override, 'email' => $override['email'] ?? "case{$i}@example.org"]))
                ->assertSessionHasErrors(array_keys($override));
        }
        $this->assertSame(0, ConsultationRequest::count());
        $this->assertNotEmpty($calendar->dates());
    }

    public function test_bots_are_stopped(): void
    {
        // Honeypot filled: looks like success, nothing stored or sent.
        $this->post('/en/consultation', $this->valid(['website' => 'http://spam']))->assertRedirect('/en/consultation/thank-you');
        // Submitted too fast, or with a forged / missing token.
        $this->post('/en/consultation', $this->valid(['form_token' => encrypt((string) time())]))->assertSessionHasErrors(['form_token' => 'expired']);
        $this->post('/en/consultation', $this->valid(['form_token' => 'forged']))->assertSessionHasErrors('form_token');
        $this->post('/en/consultation', $this->valid(['form_token' => encrypt((string) (time() - 5 * 3600))]))->assertSessionHasErrors('form_token');

        $this->assertSame(0, ConsultationRequest::count());
        Mail::assertNothingSent();
    }

    public function test_repeated_bookings_are_rate_limited(): void
    {
        $slots = ['10:00', '11:30', '13:00', '14:30'];
        foreach ($slots as $slot) {
            $this->post('/en/consultation', $this->valid(['preferred_slot' => $slot]));
        }
        $this->assertSame(3, ConsultationRequest::count()); // three per email per day
        $this->post('/en/consultation', $this->valid(['preferred_slot' => '16:00']))->assertSessionHasErrors(['form' => 'too_many']);
    }

    public function test_mail_failure_keeps_the_booking_and_records_the_error(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));
        $this->post('/en/consultation', $this->valid())->assertRedirect();
        $booking = ConsultationRequest::sole();
        $this->assertStringContainsString('SMTP down', (string) $booking->mail_error);
        $this->assertNull($booking->notified_at);
    }

    public function test_staff_reply_is_emailed_and_recorded(): void
    {
        $this->post('/en/consultation', $this->valid());
        $booking = ConsultationRequest::sole();
        $manager = User::factory()->withRole(Role::SalesManager)->create();
        $reply = $booking->replies()->create(['user_id' => $manager->id, 'subject' => "Your meeting\r\nBcc: x@evil.test", 'body' => "Hello,\n<b>see you</b>"]);

        $this->assertTrue(app(Notifier::class)->sendReply($reply));
        Mail::assertSent(ConsultationReplyMail::class, function ($m) {
            $html = $m->render();

            return $m->hasTo('sara@example.org') && ! str_contains($m->envelope()->subject, "\n")
                && str_contains($html, '&lt;b&gt;see you&lt;/b&gt;') && ! str_contains($html, '<b>see you</b>');
        });
        $this->assertNotNull($reply->fresh()->sent_at);
    }

    public function test_dashboard_access_follows_roles_and_assignment(): void
    {
        $this->post('/en/consultation', $this->valid());
        $booking = ConsultationRequest::sole();
        $this->withoutMiddleware(EnsurePrivilegedRolesUseMfa::class);

        $manager = User::factory()->withRole(Role::SalesManager)->create();
        $this->actingAs($manager)->get('/admin/consultation-requests')->assertOk()->assertSee($booking->reference);
        $this->actingAs($manager)->get("/admin/consultation-requests/{$booking->id}")->assertOk()->assertSee('sara@example.org');
        $this->actingAs($manager)->get('/admin/notification-recipients')->assertOk()->assertSee('sales@example.org');

        // An agent sees only bookings assigned to them (others do not even exist for them), and cannot manage recipients.
        $agent = User::factory()->withRole(Role::SalesAgent)->create();
        $this->actingAs($agent)->get('/admin/consultation-requests')->assertOk()->assertDontSee($booking->reference);
        $this->actingAs($agent)->get("/admin/consultation-requests/{$booking->id}")->assertNotFound();
        $this->actingAs($agent)->get('/admin/notification-recipients')->assertForbidden();
        $booking->update(['assigned_to' => $agent->id]);
        $this->actingAs($agent)->get("/admin/consultation-requests/{$booking->id}")->assertOk();

        // Content roles never see client data.
        $editor = User::factory()->withRole(Role::Editor)->create();
        $this->actingAs($editor)->get('/admin/consultation-requests')->assertForbidden();
    }
}
