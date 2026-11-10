<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\NotificationRecipients\Pages\ManageNotificationRecipients;
use App\Mail\RecipientAdded;
use App\Mail\SignInCodeMail;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Notifications\SignInCode;
use Database\Seeders\RbacSeeder;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/** Dashboard sign-in finishes with a code emailed to the person; new booking recipients are welcomed by email. */
class SignInCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_signing_in_needs_the_code_sent_to_the_email(): void
    {
        Notification::fake();
        $user = User::factory()->withRole(Role::Editor)->create(['password' => 'Long-Password-123456']);

        $login = Livewire::test(Login::class)->fillForm(['email' => $user->email, 'password' => 'Long-Password-123456'])->call('authenticate');
        $this->assertGuest();

        $code = null;
        Notification::assertSentTo($user, SignInCode::class, function (SignInCode $n) use (&$code) {
            $code = $n->code;

            return $n->codeExpiryMinutes === 10;
        });
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        // A wrong code is refused
        $login->set('data.multiFactor.email_code.code', $code === '000000' ? '111111' : '000000')->call('authenticate')->assertHasErrors();
        $this->assertGuest();

        $login->set('data.multiFactor.email_code.code', $code)->call('authenticate')->assertHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_code_email_is_sent_at_once_in_the_dashboard_language(): void
    {
        $user = User::factory()->withRole(Role::Editor)->create(['admin_locale' => 'ar']);
        $mail = (new SignInCode('482913', 10))->toMail($user);
        $this->assertInstanceOf(SignInCodeMail::class, $mail);
        $this->assertNotInstanceOf(ShouldQueue::class, new SignInCode('1', 1));
        $html = $mail->render();
        $this->assertStringContainsString('482913', $html);
        $this->assertStringContainsString('كود', $html);
        $this->assertTrue($mail->hasTo($user->email));
    }

    public function test_a_new_booking_recipient_is_welcomed(): void
    {
        Mail::fake();
        $admin = User::factory()->withRole(Role::SuperAdmin)->create(['name' => 'Mohamed']);
        Livewire::actingAs($admin)->test(ManageNotificationRecipients::class)
            ->callAction('create', ['name' => 'Sara', 'email' => 'sara@example.org', 'active' => true])->assertHasNoActionErrors();

        $this->assertNotNull(NotificationRecipient::where('email', 'sara@example.org')->first());
        Mail::assertSent(RecipientAdded::class, fn (RecipientAdded $m) => $m->hasTo('sara@example.org') && str_contains($m->render(), 'Mohamed'));
    }
}
