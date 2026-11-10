<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Widgets\BookingsByServiceChart;
use App\Filament\Widgets\BookingStatsWidget;
use App\Filament\Widgets\ContentOverviewWidget;
use App\Filament\Widgets\UpcomingConsultations;
use App\Filament\Widgets\WelcomeWidget;
use App\Models\ConsultationRequest;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Dashboard home: language switch, widgets and who sees them. */
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RbacSeeder::class, ContentSeeder::class]);
    }

    private function booking(array $overrides = []): ConsultationRequest
    {
        return ConsultationRequest::create([
            'reference' => 'CM-'.fake()->unique()->numerify('######-####'), 'name' => 'Client', 'email' => 'c@example.org', 'phone' => '+966500000000',
            'meeting_mode' => 'video', 'preferred_date' => now()->addDays(2)->toDateString(), 'preferred_slot' => '10:00',
            'locale' => 'en', 'consent_at' => now(), 'service' => 'company-formation', 'country' => 'eg', ...$overrides,
        ]);
    }

    public function test_staff_switch_the_dashboard_language(): void
    {
        $user = User::factory()->withRole(Role::SalesManager)->create();
        $this->actingAs($user)->post('/admin/locale', ['locale' => 'en'])->assertRedirect();
        $this->assertSame('en', $user->fresh()->admin_locale);
        $this->actingAs($user)->get('/admin')->assertOk()->assertSee('lang="en"', false);

        $this->actingAs($user)->post('/admin/locale', ['locale' => 'ar']);
        $this->actingAs($user)->get('/admin')->assertSee('lang="ar"', false)->assertSee('dir="rtl"', false);
        $this->actingAs($user)->post('/admin/locale', ['locale' => 'fr'])->assertSessionHasErrors('locale');
        auth()->logout();
        $this->post('/admin/locale', ['locale' => 'en'])->assertRedirect(); // guests are sent to login
        $this->assertSame('ar', $user->fresh()->admin_locale);
    }

    public function test_managers_see_booking_figures(): void
    {
        $this->booking();
        $this->booking(['preferred_slot' => '11:30', 'service' => null]);
        $manager = User::factory()->withRole(Role::SalesManager)->create();
        $this->actingAs($manager);

        Livewire::test(BookingStatsWidget::class)->assertOk()->assertSee('2');
        Livewire::test(UpcomingConsultations::class)->assertOk()->assertSee('Client');
        Livewire::test(BookingsByServiceChart::class)->assertOk();
        Livewire::test(WelcomeWidget::class)->assertOk()->assertSee(__('Review bookings'));
        $this->get('/admin')->assertOk();
    }

    public function test_widgets_follow_permissions(): void
    {
        $this->actingAs(User::factory()->withRole(Role::Editor)->create());
        $this->assertFalse(BookingStatsWidget::canView());
        $this->assertFalse(UpcomingConsultations::canView());
        $this->assertTrue(ContentOverviewWidget::canView());
        Livewire::test(WelcomeWidget::class)->assertOk()->assertDontSee(__('Review bookings'));

        $this->actingAs(User::factory()->withRole(Role::SalesAgent)->create());
        $this->assertTrue(BookingStatsWidget::canView());
        $this->assertFalse(ContentOverviewWidget::canView());
    }

    public function test_agents_only_count_their_own_bookings(): void
    {
        $agent = User::factory()->withRole(Role::SalesAgent)->create();
        $this->booking(['assigned_to' => $agent->id, 'name' => 'Mine']);
        $this->booking(['preferred_slot' => '13:00', 'name' => 'Someone else']);
        $this->actingAs($agent);
        Livewire::test(UpcomingConsultations::class)->assertSee('Mine')->assertDontSee('Someone else');
    }
}
